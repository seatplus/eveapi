<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Actions\Location\CacheAllPublicStrucutresIdAction;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;

class ResolvePublicStructureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * @var string
     */
    public $refresh_token;

    private $location_ids;

    /**
     * @var string
     */
    private $cache_string;

    public function __construct(JobContainer $job_container)
    {

        $this->refresh_token = $job_container->getRefreshToken();
        $this->cache_string = 'new_public_structure_ids';
    }

    public function middleware(): array
    {
        return [
            new RedisFunnelMiddleware,
            new HasRefreshTokenMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
        ];
    }

    public function tags() {
        return [
            'public structures',
        ];
    }

    public function handle(): void
    {

        if(! cache()->has($this->cache_string))
            (new CacheAllPublicStrucutresIdAction)->execute();

        $this->location_ids = collect(cache()->pull($this->cache_string));

        $this->location_ids->unique()->each(function ($location_id) {

            dispatch(new ResolveLocationJob($location_id, $this->refresh_token))->onQueue('default');
        });

    }
}
