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

namespace Seatplus\Eveapi\Jobs\Character;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Esi\HasRequestBodyInterface;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Services\Jobs\CharacterAffiliationService;
use Seatplus\Eveapi\Traits\HasRequestBody;

class CharacterAffiliationJob extends EsiBase implements HasRequestBodyInterface
{
    use HasRequestBody;

    private array $manual_ids = [];

    public function __construct(int|array|null $character_ids = null)
    {
        parent::__construct(
            method: 'post',
            endpoint: '/characters/affiliation/',
            version: 'v2',
        );

        $this->setManualIds($character_ids);
    }

    public function tags(): array
    {
        return [
            'character',
            'affiliation',
        ];
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            ...parent::middleware(),
        ];
    }

    /**
     * Execute the job.
     *
     * @throws \Exception
     */
    public function executeJob(): void
    {
        if ($this->manual_ids) {
            $this->updateOrCreateCharacterAffiliations($this->manual_ids);
        }

        if (! $this->manual_ids) {
            Redis::throttle('character_affiliations')
                // allow one job to process every 5 minutes
                ->block(0)->allow(1)->every(5 * 60)
                ->then(function () {
                    collect()
                        ->merge($this->getIdsToUpdateFromCache())
                        ->merge($this->getIdsToUpdateFromDatabase())
                        ->chunk(1000)
                        ->each(fn ($chunk) => $this->updateOrCreateCharacterAffiliations($chunk->toArray()));
                }, fn () => $this->delete());
        }
    }

    private function getIdsToUpdateFromCache(): Collection
    {
        return CharacterAffiliationService::make()->retrieve()->unique();
    }

    private function getIdsToUpdateFromDatabase(): Collection
    {
        return CharacterAffiliation::query()
            // only those who were not pulled within the last hour
            ->where('last_pulled', '<=', now()->subHour()->toDateTimeString())
            // and don't try doomheimed characters
            ->where('corporation_id', '<>', 1_000_001)
            ->pluck('character_id');
    }

    private function updateOrCreateCharacterAffiliations(array $character_ids): void
    {
        $this->setRequestBody($character_ids);

        $timestamp = now();

        $character_affiliations = collect();

        // try to get the character affiliations from the esi endpoint
        try {
            $response = $this->retrieve();

            collect($response)
                ->each(fn ($result) => $character_affiliations->push(
                    [
                        'character_id' => $result->character_id,
                        'corporation_id' => $result->corporation_id,
                        'alliance_id' => data_get($result, 'alliance_id'),
                        'faction_id' => data_get($result, 'faction_id'),
                        'last_pulled' => $timestamp,
                    ]
                ));
        } catch (RequestFailedException $exception) {
            // if the request fails, we perform a binary search to find the character ids that are not valid
            // if the request fails and the character ids are less than 2, we can assume that the character id is invalid
            if (count($character_ids) === 1) {
                // dispatch a new character_info job to update the character info if it is member of doomheim
                CharacterInfoJob::dispatch($character_ids[0])->onQueue('low');

                // cache the invalid character id for 1 day
                // first get the cached invalid character ids
                $invalid_character_ids = Cache::get('invalid_character_ids', []);
                // add the invalid character id to the array
                $invalid_character_ids[] = $character_ids[0];
                // cache the array
                Cache::put('invalid_character_ids', $invalid_character_ids, 60 * 24);

                return;
            }

            // if the request fails and the character ids are more than 2, we perform a binary search to find the invalid character ids
            $half = (int) ceil(count($character_ids) / 2);
            $first_half = array_slice($character_ids, 0, $half);
            $second_half = array_slice($character_ids, $half);

            $this->updateOrCreateCharacterAffiliations($first_half);
            $this->updateOrCreateCharacterAffiliations($second_half);
        }

        CharacterAffiliation::upsert(
            $character_affiliations->toArray(),
            ['character_id'],
            ['corporation_id', 'alliance_id', 'faction_id', 'last_pulled']
        );

        $this->followUp();
    }

    public function getManualIds(): array
    {
        return $this->manual_ids;
    }

    public function setManualIds(int|array|null $manual_ids): void
    {
        if (is_null($manual_ids)) {
            return;
        }

        $manual_ids = is_array($manual_ids) ? $manual_ids : [$manual_ids];

        $this->manual_ids = $manual_ids;
    }

    private function followUp()
    {
        $this->getMissingCorporations();
        $this->getMissingAlliances();
    }

    private function getMissingCorporations(): void
    {
        CharacterAffiliation::query()
            ->has('character')
            ->whereDoesntHave('corporation')
            ->pluck('corporation_id')
            ->unique()
            ->each(fn ($corporation_id) => CorporationInfoJob::dispatch($corporation_id)->onQueue('high'));
    }

    private function getMissingAlliances(): void
    {
        CharacterAffiliation::query()
            ->has('character')
            ->whereNotNull('alliance_id')
            ->whereDoesntHave('alliance')
            ->pluck('alliance_id')
            ->unique()
            ->each(fn ($alliance_id) => AllianceInfoJob::dispatch($alliance_id)->onQueue('high'));
    }
}
