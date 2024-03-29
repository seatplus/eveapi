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

use Seatplus\Eveapi\Esi\HasCorporationRoleInterface;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Traits\HasCorporationRole;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class CorporationMemberTrackingJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface, HasCorporationRoleInterface
{
    use HasPathValues;
    use HasRequiredScopes;
    use HasCorporationRole;

    public function __construct(
        public int $corporation_id
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/corporations/{corporation_id}/membertracking/',
            version: 'v2',
        );

        $this->setRequiredScope('esi-corporations.track_members.v1');

        $this->setCorporationRoles('Director');

        $this->setPathValues([
            'corporation_id' => $this->corporation_id,
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            ...parent::middleware(),
        ];
    }

    public function tags(): array
    {
        return [
            'corporation',
            'corporation_id: '.$this->corporation_id,
            'member',
            'tracking',
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

        $members = collect($response)
            ->map(fn ($member) => [
                'corporation_id' => $this->corporation_id,
                'character_id' => $member->character_id,
                'start_date' => property_exists($member, 'start_date') ? carbon($member->start_date) : null,
                'base_id' => $member->base_id ?? null,
                'logon_date' => property_exists($member, 'logon_date') ? carbon($member->logon_date) : null,
                'logoff_date' => property_exists($member, 'logoff_date') ? carbon($member->logoff_date) : null,
                'location_id' => $member->location_id ?? null,
                'ship_type_id' => $member->ship_type_id ?? null,

            ]);

        $this->upsertMembers($members);
        $this->removeOldMembers($members);
        $this->getMemberCharacterInfo();
        $this->getLocations();
        $this->getShipTypes();
    }

    private function upsertMembers(\Illuminate\Support\Collection $members)
    {
        CorporationMemberTracking::upsert($members->toArray(), ['corporation_id', 'character_id']);
    }

    private function removeOldMembers(\Illuminate\Support\Collection $members)
    {
        CorporationMemberTracking::where('corporation_id', $this->corporation_id)
            ->whereNotIn('character_id', $members->pluck('character_id')->all())
            // in order to use model events we must actually receive the models and delete them individually
            ->get()
            ->each(fn ($ex_member) => $ex_member->delete());
    }

    private function getLocations()
    {
        $refresh_token = $this->getRefreshToken();

        CorporationMemberTracking::query()
            ->where('corporation_id', $this->corporation_id)
            ->doesntHave('location')
            ->pluck('location_id')
            ->unique()
            ->each(fn ($location_id) => ResolveLocationJob::dispatch($location_id, $refresh_token)->onQueue('high'));
    }

    private function getMemberCharacterInfo()
    {
        CorporationMemberTracking::query()
            ->where('corporation_id', $this->corporation_id)
            ->doesntHave('character')
            ->pluck('character_id')
            ->unique()
            ->each(fn ($character_id) => CharacterInfoJob::dispatch($character_id)->onQueue('high'));
    }

    private function getShipTypes()
    {
        CorporationMemberTracking::query()
            ->where('corporation_id', $this->corporation_id)
            ->doesntHave('ship')
            ->pluck('ship_type_id')
            ->unique()
            ->each(fn ($ship_type_id) => ResolveUniverseTypeByIdJob::dispatch($ship_type_id)->onQueue('high'));
    }
}
