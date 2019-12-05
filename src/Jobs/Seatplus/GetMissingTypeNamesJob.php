<?php


namespace Seatplus\Eveapi\Jobs\Seatplus;


use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\Universe\NamesAction;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;

class GetMissingTypeNamesJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Illuminate\Support\Collection
     */
    public $type_ids;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware() : array
    {
        return [
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
        ];
    }

    public function tags(): array
    {

        return [
            'type_names',
            'cleanup'
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        (new NamesAction)->execute();

    }

}
