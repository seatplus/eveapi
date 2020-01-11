<?php

namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseGroupsByGroupIdAction;
use Seatplus\Eveapi\Actions\Seatplus\CacheMissingCategoryIdsAction;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;

class ResolveUniverseGroupsByGroupIdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

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
            new RedisFunnelMiddleware,
        ];
    }

    public function tags(): array
    {

        return [
            'type',
            'informations',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        (new ResolveUniverseGroupsByGroupIdAction)->execute();

        (new CacheMissingCategoryIdsAction)->execute();

    }
}
