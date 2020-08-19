<?php


namespace Seatplus\Eveapi\Services\Pipes\Corporation;


use Closure;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;
use Seatplus\Eveapi\Services\Pipes\Pipe;

abstract class AbstractCorporationPipe implements Pipe
{

    abstract public function getRequiredScope() : string;

    abstract public function getRequiredRole(): string;

    abstract public function handle(JobContainer $job_container, Closure $next);

    public final function enrichJobContainerWithRefreshToken(JobContainer $job_container) : JobContainer
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
