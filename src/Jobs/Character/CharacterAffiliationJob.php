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

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Esi\HasRequestBodyInterface;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Traits\HasRequestBody;

class CharacterAffiliationJob extends EsiBase implements HasRequestBodyInterface
{
    use HasRequestBody;

    private array $manual_ids = [];

    private Collection $character_affiliations;

    /**
     * @throws \Throwable
     */
    public function __construct(int|array $character_ids)
    {
        parent::__construct(
            method: 'post',
            endpoint: '/characters/affiliation/',
            version: 'v2',
        );

        $this->setManualIds($character_ids);

        // throw error if manual_ids is not larger than 1000
        throw_unless(count($this->manual_ids) <= 1000, new \Exception('Character ids must not exceed 1000'));

        $this->character_affiliations = collect();
    }

    #[\Override]
    public function tags(): array
    {
        return [
            'character',
            'affiliation',
        ];
    }

    /**
     * Execute the job.
     *
     * @throws \Exception
     */
    #[\Override]
    public function executeJob(): void
    {

        $this->updateOrCreateCharacterAffiliations($this->getManualIds());

        CharacterAffiliation::query()->upsert(
            $this->character_affiliations->toArray(),
            ['character_id'],
            ['corporation_id', 'alliance_id', 'faction_id', 'last_pulled']
        );

        $this->followUp();
    }

    public function processResponse(EsiResponse $response, Carbon $timestamp): void
    {
        collect($response->data)
            ->each(fn (object $result) => $this->character_affiliations->push(
                [
                    'character_id' => $result->character_id,
                    'corporation_id' => $result->corporation_id,
                    'alliance_id' => data_get($result, 'alliance_id'),
                    'faction_id' => data_get($result, 'faction_id'),
                    'last_pulled' => $timestamp,
                ]
            ));
    }

    private function updateOrCreateCharacterAffiliations(array $character_ids): void
    {
        $this->setRequestBody($character_ids);
        $timestamp = now();

        // try to get the character affiliations from the esi endpoint
        try {
            $response = $this->retrieve();

            $this->processResponse($response, $timestamp);
        } catch (RequestFailedException) {
            $this->handleFailedRequest($character_ids);
        }
    }

    public function getManualIds(): array
    {
        return $this->manual_ids;
    }

    public function setManualIds(int|array $manual_ids): void
    {
        $manual_ids = is_array($manual_ids) ? $manual_ids : [$manual_ids];

        $this->manual_ids = $manual_ids;
    }

    private function followUp(): void
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
            ->each(fn (int $corporation_id) => CorporationInfoJob::dispatch($corporation_id)->onQueue('high'));
    }

    private function getMissingAlliances(): void
    {
        CharacterAffiliation::query()
            ->has('character')
            ->whereNotNull('alliance_id')
            ->whereDoesntHave('alliance')
            ->pluck('alliance_id')
            ->unique()
            ->each(fn (int $alliance_id) => AllianceInfoJob::dispatch($alliance_id)->onQueue('high'));
    }

    private function handleFailedRequest(array $character_ids): void
    {
        // if the request fails and the character ids are less than 2, we can assume that the character id is invalid
        if (count($character_ids) === 1) {
            $this->handleSingleIdException($character_ids[0]);

            return;
        }

        // if the request fails, we perform a binary search to find the character ids that are not valid
        $this->handleMultipleIdsException($character_ids);

    }

    private function handleSingleIdException(int $character_id): void
    {

        // dispatch a new character_info job to update the character info if it is member of doomheim
        CharacterInfoJob::dispatch($character_id)->onQueue('low');

        // cache the invalid character id for 1 day
        // first get the cached invalid character ids
        $invalid_character_ids = Cache::get('invalid_character_ids', []);
        // add the invalid character id to the array
        $invalid_character_ids[] = $character_id;
        // cache the array
        Cache::put('invalid_character_ids', $invalid_character_ids, 60 * 24);
    }

    private function handleMultipleIdsException(array $character_ids): void
    {
        // if the request fails and the character ids are more than 2, we perform a binary search to find the invalid character ids
        $half = (int) ceil(count($character_ids) / 2);
        $first_half = array_slice($character_ids, 0, $half);
        $second_half = array_slice($character_ids, $half);

        $this->updateOrCreateCharacterAffiliations($first_half);
        $this->updateOrCreateCharacterAffiliations($second_half);
    }
}
