<?php

namespace Seatplus\Eveapi\Jobs\Assets;

use Seatplus\Eveapi\Actions\Jobs\Assets\CharacterAssetsAction;
use Seatplus\Eveapi\Actions\Jobs\BaseActionJobInterface;
use Seatplus\Eveapi\Actions\Seatplus\CacheMissingCharacterTypeIdsAction;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;

class CharacterAssetJob extends EsiBase
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
            new EsiAvailabilityMiddleware,
        ];
    }

    public function tags(): array
    {

        return [
            'character',
            'character_id: ' . $this->character_id,
            'assets',
            ];
    }

    public function getActionClass() : BaseActionJobInterface
    {
        return new CharacterAssetsAction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() :void
    {
        $this->getActionClass()->execute($this->refresh_token);

        (new CacheMissingCharacterTypeIdsAction)->execute();
    }
}
