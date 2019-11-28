<?php


namespace Seatplus\Eveapi\Jobs\Middleware;


use Illuminate\Support\Facades\Redis;

class RedisFunnelMiddleware
{
    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {

        $key = (string) get_class($job) . ':' . implode($job->tags());

        Redis::funnel($key)->limit(1)->then(function () use ($job, $next){

            return $next($job);

        });

        return $job->delete();

    }

}
