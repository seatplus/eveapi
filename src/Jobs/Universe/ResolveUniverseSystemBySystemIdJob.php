<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseSystemsBySystemIdAction;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;

class ResolveUniverseSystemBySystemIdJob extends EsiBase
{

    private $system_id;

    public function getActionClass(): RetrieveFromEsiInterface
    {
        return new ResolveUniverseSystemsBySystemIdAction;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new RedisFunnelMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
        ];
    }

    public function tags(): array
    {
        return [
            'system_resolve',
            'system_id:' . $this->system_id,
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->getActionClass()->execute($this->system_id);
    }

    /**
     * @param int $system_id
     *
     * @return ResolveUniverseSystemBySystemIdJob
     */
    public function setSystemId(int $system_id)
    {

        $this->system_id = $system_id;

        return $this;
}
}
