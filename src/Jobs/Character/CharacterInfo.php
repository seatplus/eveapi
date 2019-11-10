<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\Eveapi\Actions\Jobs\Corporation\CorporationInfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailability;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;

class CharacterInfo extends EsiBase
{

    //TODO Implement JobMiddlewareForRateLimit
    /**
     * @var array
     */
    protected $tags = ['character', 'info'];

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware() : array
    {
        return [
            new EsiRateLimitedMiddleware,
            new EsiAvailability,
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {

        $this->getActionClass()->execute($this->character_id);

    }

    public function getActionClass()
    {
        return new CorporationInfoAction;
    }
}
