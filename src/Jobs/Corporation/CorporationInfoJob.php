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

use Seatplus\Eveapi\DataTransferObjects\Responses\Corporation\CorporationInfoResponse;
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

        $data = CorporationInfoResponse::from($response->data);

        CorporationInfo::firstOrNew(['corporation_id' => $this->corporation_id])->fill([
            'ticker' => $data->ticker,
            'name' => $data->name,
            'member_count' => $data->member_count,
            'ceo_id' => $data->ceo_id,
            'creator_id' => $data->creator_id,
            'tax_rate' => $data->tax_rate,
            'alliance_id' => $data->alliance_id,
            'date_founded' => $data->date_founded !== null ? carbon($data->date_founded) : null,
            'description' => $data->description,
            'faction_id' => $data->faction_id,
            'home_station_id' => $data->home_station_id,
            'shares' => $data->shares,
            'url' => $data->url,
            'war_eligible' => $data->war_eligible,
        ])->save();
    }
}
