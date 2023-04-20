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

use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Character\CorporationHistory;
use Seatplus\Eveapi\Traits\HasPathValues;

class CorporationHistoryJob extends EsiBase implements HasPathValuesInterface
{
    use HasPathValues;

    public function __construct(
        public int $character_id
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/characters/{character_id}/corporationhistory/',
            version: 'v2',
        );

        $this->setPathValues([
            'character_id' => $character_id,
        ]);
    }

    public function tags(): array
    {
        return [
            'character',
            'info',
            'character_id:'.$this->character_id,
            'corporationhistory',
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
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        $results = collect($response)->map(fn ($record) => [
            'record_id' => data_get($record, 'record_id'),
            'character_id' => $this->character_id,
            'corporation_id' => data_get($record, 'corporation_id'),
            'is_deleted' => data_get($record, 'is_deleted'),
            'start_date' => carbon(data_get($record, 'start_date')),
        ]);

        CorporationHistory::query()->upsert(
            $results->toArray(),
            ['record_id'],
            // only the is_deleted column could be updated
            ['is_deleted']
        );
    }
}
