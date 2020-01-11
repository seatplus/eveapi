<?php

namespace Seatplus\Eveapi\Jobs\Corporation;

use Seatplus\Eveapi\Actions\Jobs\Corporation\CorporationInfoAction;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;

class CorporationInfoJob extends EsiBase
{

    /**
     * @var array
     */
    protected $tags = ['corporation', 'info'];

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {

        $this->getActionClass()->execute($this->corporation_id);

    }

    public function getActionClass(): RetrieveFromEsiInterface
    {
        return new CorporationInfoAction;
    }
}
