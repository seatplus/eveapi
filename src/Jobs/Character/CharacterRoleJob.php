<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\Eveapi\Actions\Jobs\Character\CharacterRoleAction;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailability;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;

class CharacterRoleJob extends EsiBase
{

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware() : array
    {
        return [
            new HasRefreshTokenMiddleware,
            new HasRequiredScopeMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailability,
        ];
    }

    public function tags(): array
    {

        return [
            'character',
            'character_id: ' . $this->character_id,
            'roles'
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

        $this->getActionClass()->execute($this->refresh_token);

    }

    public function getActionClass()
    {
        return new CharacterRoleAction;
    }
}
