<?php

namespace Seatplus\Eveapi\Services\Esi;

use Illuminate\Support\Facades\Cache;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Models\RefreshToken;

class GetUpToDateRefreshTokenService
{
    public function __construct(
        private ?UpdateRefreshTokenService $updateRefreshTokenService = null
    ) {
        $this->updateRefreshTokenService ??= new UpdateRefreshTokenService;
    }

    /**
     * @throws RequestFailedException
     */
    public function __invoke(RefreshToken $refresh_token): RefreshToken
    {
        $character_id = $refresh_token->character_id;

        return Cache::lock("get up to date refresh_token of character_id: {$character_id}", 10)
            ->get(function () use ($refresh_token) {
                $token = $refresh_token->refresh();

                if (carbon($token->expires_on)->gt(now()->addMinute())) {
                    return $token;
                }

                return $this->updateRefreshTokenService->update($token);
            });
    }
}
