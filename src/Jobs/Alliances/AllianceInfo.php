<?php

namespace Seatplus\Eveapi\Jobs\Alliances;

use Seatplus\Eveapi\Actions\Jobs\Alliance\AllianceInfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailability;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;

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
        return [
            new EsiRateLimitedMiddleware,
            new EsiAvailability,
        ];
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
        $this->getActionClass()->execute($this->alliance_id);
    }

    public function getActionClass()
    {
        return new AllianceInfoAction;
    }
}
