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

namespace Seatplus\Eveapi\Jobs\Corporation;

use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Traits\HasPathValues;

class CorporationInfoJob extends EsiBase implements HasPathValuesInterface
{
    use HasPathValues;

    public function __construct(
        public int $corporation_id
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/corporations/{corporation_id}/',
            version: 'v5',
        );

        $this->setPathValues([
            'corporation_id' => $this->corporation_id,
        ]);
    }

    #[\Override]
    public function tags(): array
    {
        return [
            'corporation',
            'info',
            'corporation_id:'.$this->corporation_id,
        ];
    }

    /**
     * Execute the job.
     */
    #[\Override]
    public function executeJob(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        CorporationInfo::firstOrNew(['corporation_id' => $this->corporation_id])->fill([
            'ticker' => $response->data->ticker,
            'name' => $response->data->name,
            'member_count' => $response->data->member_count,
            'ceo_id' => $response->data->ceo_id,
            'creator_id' => $response->data->creator_id,
            'tax_rate' => $response->data->tax_rate,
            'alliance_id' => data_get($response->data, 'alliance_id'),
            'date_founded' => property_exists($response->data, 'date_founded') ?
                carbon($response->data->date_founded) : null,
            'description' => data_get($response->data, 'description'),
            'faction_id' => data_get($response->data, 'faction_id'),
            'home_station_id' => data_get($response->data, 'home_station_id'),
            'shares' => data_get($response->data, 'shares'),
            'url' => data_get($response->data, 'url'),
            'war_eligible' => data_get($response->data, 'war_eligible'),
        ])->save();
    }
}
