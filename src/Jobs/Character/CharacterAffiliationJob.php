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

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Facades\Redis;
use Seatplus\Eveapi\Esi\HasRequestBodyInterface;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Services\Jobs\CharacterAffiliationService;
use Seatplus\Eveapi\Traits\HasRequestBody;

class CharacterAffiliationJob extends NewEsiBase implements HasRequestBodyInterface
{
    use HasRequestBody;

    private array $manual_ids = [];

    public function __construct(int|array|null $character_ids = null)
    {
        $this->setJobType('public');
        parent::__construct();

        $this->setMethod('post');
        $this->setEndpoint('/characters/affiliation/');
        $this->setVersion('v2');

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
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by('esiratelimit')
                ->backoff(5),
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {

        if($this->manual_ids) {
            $this->updateOrCreateCharacterAffiliations($this->manual_ids);
        }



        if(!$this->manual_ids) {
            Redis::throttle('character_affiliations')
                // allow one job to process every 5 minutes
                ->block(0)->allow(1)->every(5*60)
                ->then(function () {
                    $this->updateCachedCharacterAffiliations();
                    $this->updateOutdatedCharacterAffiliations();
                }, fn() => $this->delete());
        }


    }

    private function updateCachedCharacterAffiliations()
    {
        $ids = CharacterAffiliationService::make()->retrieve()->unique();

        if($ids->count() > 0) {
            $this->updateOrCreateCharacterAffiliations($ids->toArray());
        }
    }

    private function updateOrCreateCharacterAffiliations(array $character_ids) : void
    {
        $this->setRequestBody($character_ids);

        $response = $this->retrieve();

        $timestamp = now();

        collect($response)
            ->each(fn ($result) => CharacterAffiliation::updateOrCreate(
                [
                    'character_id' => $result->character_id,
                ],
                [
                    'corporation_id' => $result->corporation_id,
                    'alliance_id' => $result?->alliance_id,
                    'faction_id' => $result?->faction_id,
                    'last_pulled' => $timestamp,
                ]
            ));
    }

    private function updateOutdatedCharacterAffiliations()
    {

        $ids = CharacterAffiliation::query()
            // only those who were not pulled within the last hour
            ->where('last_pulled', '<=', now()->subHour()->toDateTimeString())
            // and don't try doomheimed characters
            ->where('character_id', '<>', 1_000_001)
            ->pluck('character_id');

        $ids->chunk(1000)
            ->each(fn ($chunk) => $this->updateOrCreateCharacterAffiliations($chunk->toArray()));
    }

    /**
     * @return array
     */
    public function getManualIds(): array
    {
        return $this->manual_ids;
    }

    /**
     * @param int|array|null $manual_ids
     */
    public function setManualIds(int|array|null $manual_ids): void
    {

        if(is_null($manual_ids))
            return;

        $manual_ids = is_array($manual_ids) ? $manual_ids : [$manual_ids];

        $this->manual_ids = $manual_ids;
    }
}
