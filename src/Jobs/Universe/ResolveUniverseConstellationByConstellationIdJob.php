<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseConstellationByConstellationIdAction;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;

class ResolveUniverseConstellationByConstellationIdJob extends EsiBase
{

    private $constellation_id;

    public function getActionClass(): RetrieveFromEsiInterface
    {
        return new ResolveUniverseConstellationByConstellationIdAction;
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
            'constellation_resolver',
            'constellation_id:' . $this->constellation_id,
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->getActionClass()->execute($this->constellation_id);
    }

    /**
     * @param int $constellation_id
     *
     * @return \Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob
     */
    public function setConstellationId(int $constellation_id)
    {

        $this->constellation_id = $constellation_id;

        return $this;
    }
}
