<?php

namespace Seatplus\Eveapi\Jobs\Killmails;

use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Killmails\GetKillmailsKillmailIdKillmailHash;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Killmails\Killmail;
use Seatplus\Eveapi\Models\Killmails\KillmailAttacker;
use Seatplus\Eveapi\Models\Killmails\KillmailItem;
use Seatplus\Eveapi\Services\Jobs\GetLocationFlagNameService;

final class KillmailJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetKillmailsKillmailIdKillmailHash::class;

    public function __construct(
        public int $killmail_id,
        public string $killmail_hash
    ) {}

    #[\Override]
    public function tags(): array
    {
        return ['killmails', "killmail_id:{$this->killmail_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->killmail_hash, $this->killmail_id);
        if ($response->isCachedLoad) {
            return;
        }

        $victim = (object) $response->victim;

        $killmail = Killmail::firstOrCreate(['killmail_id' => $this->killmail_id], [
            'killmail_hash' => $this->killmail_hash,
            'solar_system_id' => $response->solar_system_id,
            'victim_character_id' => $victim->character_id ?? null,
            'victim_corporation_id' => $victim->corporation_id ?? null,
            'victim_alliance_id' => $victim->alliance_id ?? null,
            'ship_type_id' => $victim->ship_type_id ?? null,
            'victim_faction_id' => $victim->faction_id ?? null,
            'damage_taken' => $victim->damage_taken ?? null,
            'complete' => true,
        ]);

        if (! $killmail->wasRecentlyCreated) {
            return;
        }

        if (is_null($killmail->system)) {
            $this->getMissingSystem($response->solar_system_id);
        }

        if (is_null($killmail->ship)) {
            $this->getMissingTypeIds(collect($victim->ship_type_id ?? null)->filter());
        }

        $this->createKillmailItems($victim->items ?? []);
        $this->createKillmailAttackers($response->attackers);
    }

    private function createKillmailItems(array $items, ?int $location_id = null): void
    {
        collect($items)->each(function (mixed $item) use ($location_id) {
            $item = (object) $item;
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

        $unknownTypeIds = KillmailItem::doesntHave('type')->pluck('type_id')->unique();
        if ($unknownTypeIds->isEmpty()) {
            return;
        }
        $this->getMissingTypeIds($unknownTypeIds);
    }

    private function createKillmailAttackers(array $attackers): void
    {
        collect($attackers)->map(fn (mixed $a) => (object) $a)->each(fn (object $attacker) => KillmailAttacker::create([
            'killmail_id' => $this->killmail_id,
            'character_id' => $attacker->character_id ?? null,
            'corporation_id' => $attacker->corporation_id ?? null,
            'alliance_id' => $attacker->alliance_id ?? null,
            'ship_type_id' => $attacker->ship_type_id ?? null,
            'weapon_type_id' => $attacker->weapon_type_id ?? null,
            'damage_done' => $attacker->damage_done ?? null,
            'final_blow' => $attacker->final_blow ?? false,
        ]));

        $unknownTypeIds = KillmailAttacker::doesntHave('ship')
            ->pluck('ship_type_id')
            ->merge(KillmailAttacker::doesntHave('weapon')->pluck('weapon_type_id'))
            ->filter()
            ->unique();

        if ($unknownTypeIds->isEmpty()) {
            return;
        }
        $this->getMissingTypeIds($unknownTypeIds);
    }

    private function getMissingTypeIds(Collection $typeIds): void
    {
        $this->batching()
            ? $this->batch()->add($typeIds->map(fn (int $typeId) => new ResolveUniverseTypeByIdJob($typeId))->toArray())
            : $typeIds->each(fn (int $typeId) => ResolveUniverseTypeByIdJob::dispatch($typeId)->onQueue($this->queue));
    }

    private function getMissingSystem(int $solarSystemId): void
    {
        $this->batching()
            ? $this->batch()->add([new ResolveUniverseSystemBySystemIdJob($solarSystemId)])
            : ResolveUniverseSystemBySystemIdJob::dispatch($solarSystemId)->onQueue($this->queue);
    }
}
