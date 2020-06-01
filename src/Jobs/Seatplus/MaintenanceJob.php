<?php


namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;
use Seatplus\Eveapi\Services\Maintenance\GetMissingCategorysPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingGroupsPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingLocationFromAssetsPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingTypesFromCharacterAssetsPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingTypesFromLocationsPipe;

class MaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    private array $pipes = [
        GetMissingTypesFromCharacterAssetsPipe::class,
        GetMissingTypesFromLocationsPipe::class,
        GetMissingGroupsPipe::class,
        GetMissingCategorysPipe::class,
        GetMissingLocationFromAssetsPipe::class
    ];

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
            'Maintenance',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        app(Pipeline::class)
            ->send(null)
            ->through($this->pipes)
            ->then(fn() => logger()->info('Maintenance job finished'));

    }

}
