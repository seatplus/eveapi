<?php

namespace Seatplus\Eveapi\Jobs\Hydrate\Corporation;

use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;

class CorporationDivisionHydrateBatch extends HydrateCorporationBase
{
    public function getRequiredScope(): string
    {
        return 'esi-corporations.read_divisions.v1';
    }

    public function getRequiredRoles(): string|array
    {
        return 'Director';
    }

    public function handle()
    {
        if ($this->job_container->getRefreshToken()) {
            $this->batch()->add([
                new CorporationDivisionsJob($this->job_container),
            ]);
        }
    }
}
