<?php


namespace Seatplus\Eveapi\Jobs\Universe;


use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseConstellationByConstellationIdAction;
use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseRegionByRegionIdAction;
use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseSystemsBySystemIdAction;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;

class ResolveUniverseRegionByRegionIdJob extends EsiBase
{

    private $region_id;

    public function getActionClass(): RetrieveFromEsiInterface
    {
        return new ResolveUniverseRegionByRegionIdAction;
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

    public function tags() : array
    {
        return [
            'region_resolver',
            'region_id:' . $this->region_id,
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->getActionClass()->execute($this->region_id);
    }

    /**
     * @param int $region_id
     *
     * @return \Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob
     */
    public function setRegionId(int $region_id)
    {

        $this->region_id = $region_id;

        return $this;
    }

}
