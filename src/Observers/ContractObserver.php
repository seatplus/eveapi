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

namespace Seatplus\Eveapi\Observers;

use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;

class ContractObserver
{
    private Contract $contract;

    /**
     * Handle the User "created" event.
     *
     * @param Asset $contract
     *
     * @return void
     */
    public function created(Contract $contract)
    {
        $this->contract = $contract;

        $this->handleLocations();
    }

    private function handleLocations()
    {
        if (is_null($this->contract->start_location_id) && is_null($this->contract->end_location_id)) {
            return;
        }

        if ($this->contract->start_location && $this->contract->end_location) {
            return;
        }

        $refresh_token = RefreshToken::find($this->contract->issuer_id) ?? RefreshToken::find($this->contract->assignee_id);

        if (is_null($refresh_token)) {
            return;
        }

        collect([$this->contract->start_location_id, $this->contract->end_location_id])
            ->filter()
            ->each(fn ($location_id) => ResolveLocationJob::dispatch($location_id, $refresh_token)->onQueue('high'));
    }
}
