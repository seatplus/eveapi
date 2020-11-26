<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Corporation;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

abstract class HydrateCorporationBase implements ShouldQueue
{

    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var JobContainer
     */
    public JobContainer $job_container;

    public function __construct(JobContainer $job_container)
    {
        $this->job_container = $this->enrichJobContainerWithRefreshToken($job_container);
    }

    abstract public function getRequiredScope(): string;

    abstract public function getRequiredRole(): string;

    abstract public function handle();

    final public function enrichJobContainerWithRefreshToken(JobContainer $job_container): JobContainer
    {
        $find_corporation_refresh_token = new FindCorporationRefreshToken;

        $job_container->refresh_token = $find_corporation_refresh_token(
            $job_container->getCorporationId(),
            $this->getRequiredScope(),
            $this->getRequiredRole()
        );

        return $job_container;
    }

}
