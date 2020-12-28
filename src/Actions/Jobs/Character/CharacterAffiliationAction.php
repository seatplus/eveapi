<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Actions\HasRequestBodyInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;

class CharacterAffiliationAction extends RetrieveFromEsiBase implements HasRequestBodyInterface
{

    /**
     * @var string
     */
    protected $method = 'post';

    /**
     * @var string
     */
    protected $endpoint = '/characters/affiliation/';

    /**
     * @var int
     */
    protected $version = 'v1';

    /**
     * @var array|null
     */
    private $request_body;

    public function execute(?int $character_id = null)
    {
        collect($character_id)->pipe(fn (Collection $collection) => $collection->isEmpty() ? $collection : $collection->filter(function ($value) {

            // Remove $character_id that is already in DB and younger then 60minutes
            $db_entry = CharacterAffiliation::find($value);

            return $db_entry
                ? $db_entry->last_pulled->diffInMinutes(now()) > 60
                : true;
        }))->pipe(function (Collection $collection) {
            //Check all other character affiliations present in DB that are younger then 60 minutes
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

                    collect($response)->map(function ($result) use ($timestamp) {
                        return CharacterAffiliation::updateOrCreate(
                            [
                                'character_id' => $result->character_id,
                            ],
                            [
                                'corporation_id' => $result->corporation_id,
                                'alliance_id' => optional($result)->alliance_id,
                                'faction_id' => optional($result)->faction_id,
                                'last_pulled' => $timestamp,
                            ]
                        );
                    })->each(function (CharacterAffiliation $character_affiliation) use ($timestamp) {
                        $character_affiliation->last_pulled = $timestamp;
                        $character_affiliation->save();
                    });
                });
            });

        return CharacterAffiliation::find($character_id);
    }

    public function getRequestBody(): array
    {
        return $this->request_body;
    }

    public function setRequestBody(array $character_ids): void
    {
        $this->request_body = $character_ids;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
