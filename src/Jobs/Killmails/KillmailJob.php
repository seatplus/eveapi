<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Jobs\Killmails;

use Exception;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Killmails\Killmail;
use Seatplus\Eveapi\Models\Killmails\KillmailAttacker;
use Seatplus\Eveapi\Models\Killmails\KillmailItem;
use Seatplus\Eveapi\Services\Jobs\GetLocationFlagNameService;
use Seatplus\Eveapi\Traits\HasPathValues;

class KillmailJob extends EsiBase implements HasPathValuesInterface
{
    use HasPathValues;

    public function __construct(
        private int $killmail_id,
        private string $killmail_hash
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/killmails/{killmail_id}/{killmail_hash}/',
            version: 'v1',
        );

        $this->setPathValues([
            'killmail_id' => $killmail_id,
            'killmail_hash' => $killmail_hash,
        ]);
    }

    public function tags(): array
    {
        return [
            'killmails',
            sprintf('killmail_id:%s', $this->killmail_id),
        ];
    }

    public function middleware(): array
    {
        return [
            ...parent::middleware(),
        ];
    }

    public function executeJob(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        try {
            $killmail = Killmail::firstOrCreate([
                'killmail_id' => $this->killmail_id,
            ], [
                'killmail_hash' => $this->killmail_hash,
                'solar_system_id' => data_get($response, 'solar_system_id'),
                'victim_character_id' => data_get($response, 'victim.character_id'),
                'victim_corporation_id' => data_get($response, 'victim.corporation_id'),
                'victim_alliance_id' => data_get($response, 'victim.alliance_id'),
                'ship_type_id' => data_get($response, 'victim.ship_type_id'),
                'victim_faction_id' => data_get($response, 'victim.faction_id'),
                'damage_taken' => data_get($response, 'victim.damage_taken'),
            ]);

            if ($killmail->complete) {
                return;
            }

            $this->cleanUp($killmail);

            $this->batching()
                ? $this->batch()->add([new ResolveUniverseSystemBySystemIdJob(data_get($response, 'solar_system_id'))])
                : ResolveUniverseSystemBySystemIdJob::dispatch(data_get($response, 'solar_system_id'))->onQueue($this->queue);

            if (is_null($killmail->ship)) {
                $this->getMissingTypeIds(collect(data_get($response, 'victim.ship_type_id')));
            }

            $this->createKillmailItems(data_get($response, 'victim.items'));

            $this->createKillmailAttackers(data_get($response, 'attackers'));

            $killmail->complete = true;
            $killmail->save();
        } catch (Exception $e) {
            $this->fail($e);
        }
    }

    private function createKillmailItems(array $items, int $location_id = null)
    {
        collect($items)->each(function ($item) use ($location_id) {
            $killmail_item = KillmailItem::create([
                'location_id' => $location_id ?? $this->killmail_id,
                'location_flag' => GetLocationFlagNameService::make()->get(data_get($item, 'flag')),
                'quantity' => data_get($item, 'quantity_dropped') ?? data_get($item, 'quantity_destroyed'),
                'type_id' => data_get($item, 'item_type_id'),
                'singleton' => data_get($item, 'singleton'),
                'dropped' => (bool) data_get($item, 'quantity_dropped'),
                'destroyed' => (bool) data_get($item, 'quantity_destroyed'),
            ]);

            $contents = data_get($item, 'items');

            if ($contents) {
                $this->createKillmailItems($contents, $killmail_item->id);
            }
        });

        $unknown_type_ids = KillmailItem::doesntHave('type')->pluck('type_id')->unique();

        $this->getMissingTypeIds($unknown_type_ids);
    }

    private function createKillmailAttackers(array $attackers)
    {
        collect($attackers)->each(fn ($attacker) => KillmailAttacker::create([
            'killmail_id' => $this->killmail_id,
            'character_id' => data_get($attacker, 'character_id'),
            'corporation_id' => data_get($attacker, 'corporation_id'),
            'alliance_id' => data_get($attacker, 'alliance_id'),
            'ship_type_id' => data_get($attacker, 'ship_type_id'),
            'weapon_type_id' => data_get($attacker, 'weapon_type_id'),
            'damage_done' => data_get($attacker, 'damage_done'),
            'final_blow' => data_get($attacker, 'final_blow'),
        ]));

        $unknown_type_ids = KillmailAttacker::doesntHave('ship')
            ->pluck('ship_type_id')
            ->merge(KillmailAttacker::doesntHave('weapon')->pluck('weapon_type_id'))
            ->filter()
            ->unique();

        $this->getMissingTypeIds($unknown_type_ids);
    }

    private function getMissingTypeIds(Collection $type_ids)
    {
        $this->batching()
            ? $this->batch()->add($type_ids->map(fn ($type_id) => new ResolveUniverseTypeByIdJob($type_id))->toArray())
            : $type_ids->each(fn ($type_id) => ResolveUniverseTypeByIdJob::dispatch($type_id)->onQueue($this->queue));
    }

    private function cleanUp(Killmail $killmail)
    {
        $killmail->attackers()->delete();
        $killmail->items()->delete();
    }
}
