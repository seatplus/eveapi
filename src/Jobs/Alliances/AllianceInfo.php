<?php

namespace Seatplus\Eveapi\Jobs\Alliances;

use Seatplus\Eveapi\Actions\Jobs\Alliance\AllianceInfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;

class AllianceInfo extends EsiBase
{
    /**
     * @var array
     */
    protected $tags = ['alliance', 'info'];

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware() : array
    {
        return [new EsiAvailabilityMiddleware];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function handle()
    {
        (new AllianceInfoAction())->execute($this->alliance_id);
    }
}
