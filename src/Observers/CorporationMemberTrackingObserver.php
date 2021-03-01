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

use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class CorporationMemberTrackingObserver
{
    /**
     * @var \Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking|null
     */
    private CorporationMemberTracking $corporation_member_tracking;

    /**
     * Handle the User "created" event.
     *
     * @param \Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking $corporation_member_tracking
     *
     * @return void
     */
    public function created(CorporationMemberTracking $corporation_member_tracking)
    {
        $this->corporation_member_tracking = $corporation_member_tracking;

        $this->handleShipTypes();
        $this->handleLocations();
        $this->handleCharacters();
    }

    /**
     * Handle the User "updating" event.
     *
     * @param \Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking $corporation_member_tracking
     *
     * @return void
     */
    public function updating(CorporationMemberTracking $corporation_member_tracking)
    {
        $this->corporation_member_tracking = $corporation_member_tracking;

        if ($corporation_member_tracking->isDirty('location_id')) {
            $this->handleLocations();
        }

        if ($corporation_member_tracking->isDirty('ship_type_id')) {
            $this->handleShipTypes();
        }
    }

    private function handleShipTypes()
    {
        if ($this->corporation_member_tracking->ship || is_null($this->corporation_member_tracking->ship_type_id)) {
            return;
        }

        ResolveUniverseTypesByTypeIdJob::dispatch($this->corporation_member_tracking->ship_type_id)->onQueue('high');
    }

    private function handleLocations()
    {
        if ($this->corporation_member_tracking->location || is_null($this->corporation_member_tracking->location_id)) {
            return;
        }

        $find_corporation_refresh_token = new FindCorporationRefreshToken;

        $refresh_token = $find_corporation_refresh_token($this->corporation_member_tracking->corporation_id, 'esi-corporations.track_members.v1', 'Director') ?? RefreshToken::find($this->corporation_member_tracking->character_id);

        $job = new ResolveLocationJob(
            $this->corporation_member_tracking->location_id,
            $refresh_token
        );

        dispatch($job)->onQueue('high');
    }

    private function handleCharacters()
    {
        if ($this->corporation_member_tracking->character) {
            return;
        }

        $job_container = new JobContainer([
            'character_id' => $this->corporation_member_tracking->character_id,
            'queue' => 'high',
        ]);

        CharacterInfoJob::dispatch($job_container)->onQueue('high');
    }
}
