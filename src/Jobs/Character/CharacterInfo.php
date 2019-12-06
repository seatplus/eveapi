<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\Eveapi\Actions\Jobs\BaseActionJobInterface;
use Seatplus\Eveapi\Actions\Jobs\Character\CharacterInfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
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
            new EsiAvailabilityMiddleware,
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle() : void
    {

        $this->getActionClass()->execute($this->character_id);

    }

    public function getActionClass(): BaseActionJobInterface
    {
        return new CharacterInfoAction();
    }
}
