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
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasRequestBodyInterface;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Traits\HasRequestBody;

class CharacterAffiliationJob extends NewEsiBase implements HasRequestBodyInterface
{
    use HasRequestBody;

    public function __construct(?JobContainer $job_container = null)
    {
        $this->setJobType('public');
        parent::__construct($job_container);

        $this->setMethod('post');
        $this->setEndpoint('/characters/affiliation/');
        $this->setVersion('v2');
    }

    public function tags(): array
    {
        $tags = [
            'character',
            'affiliation',
        ];

        return $this->getCharacterId() ? array_merge($tags, ['character_id:' . $this->getCharacterId()]) : $tags;
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
        collect($this?->character_id)
            ->pipe(fn (Collection $collection) => $collection->isEmpty() ? $collection : $collection->filter(function ($value) {

            // Remove $character_id that is already in DB and younger then 60minutes
                $db_entry = CharacterAffiliation::find($value);

                return ! $db_entry || $db_entry->last_pulled->diffInMinutes(now()) > 60;
            }))->pipe(function (Collection $collection) {
                //Check all other character affiliations present in DB that are younger than 60 minutes
                $character_affiliations = CharacterAffiliation::cursor()->filter(function ($character_affiliation) {
                    return $character_affiliation->last_pulled->diffInMinutes(now()) > 60;
                });

                foreach ($character_affiliations as $character_affiliation) {
                    $collection->push($character_affiliation->character_id);
                }

                return $collection;
            })->unique()
            ->chunk(1000)
            ->whenNotEmpty(function ($collection) {
                $collection->each(function (Collection $chunk) {
                    $this->setRequestBody($chunk->values()->all());

                    $response = $this->retrieve();

                    if ($response->isCachedLoad()) {
                        return;
                    }

                    $timestamp = now();

                    collect($response)->map(fn ($result) => CharacterAffiliation::updateOrCreate(
                        [
                            'character_id' => $result->character_id,
                        ],
                        [
                            'corporation_id' => $result->corporation_id,
                            'alliance_id' => optional($result)->alliance_id,
                            'faction_id' => optional($result)->faction_id,
                            'last_pulled' => $timestamp,
                        ]
                    ))->each(function (CharacterAffiliation $character_affiliation) use ($timestamp) {
                        $character_affiliation->last_pulled = $timestamp;
                        $character_affiliation->save();
                    });
                });
            });

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit  = 1;
    }
}
