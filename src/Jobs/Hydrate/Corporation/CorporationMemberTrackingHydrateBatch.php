<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Corporation;


use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;

class CorporationMemberTrackingHydrateBatch extends HydrateCorporationBase
{

    public function getRequiredScope(): string
    {
        return 'esi-corporations.track_members.v1';
    }

    public function getRequiredRole(): string
    {
        return 'Director';
    }

    public function handle()
    {

        if ($this->job_container->getRefreshToken()) {
            $this->batch()->add([
                new CorporationMemberTrackingJob($this->job_container)
            ]);
        }

    }
}
