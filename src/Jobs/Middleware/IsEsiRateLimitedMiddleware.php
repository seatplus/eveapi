<?php


namespace Seatplus\Eveapi\Jobs\Middleware;


use Mockery\Exception;
use Seatplus\Eveapi\Actions\Esi\GetEsiStatusAction;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

class IsEsiRateLimitedMiddleware
{
    use RateLimitsEsiCalls;

    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {

        if(! $this->isEsiRateLimited())
            $next($job);

        $job->fail(new Exception('Hitting Esi Rate Limit. Probably the scope is missing or refresh_token was deleted'));
    }

}
