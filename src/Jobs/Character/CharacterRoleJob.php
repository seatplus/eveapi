<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\Eveapi\Actions\Jobs\Character\CharacterRoleAction;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailability;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;

class CharacterRoleJob extends EsiBase
{

    /**
     * @var array
     */
    protected $tags = ['character', 'role'];

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware() : array
    {
        return [
            new HasRefreshTokenMiddleware,
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

        (new CharacterRoleAction())->execute($this->refresh_token);

    }
}
