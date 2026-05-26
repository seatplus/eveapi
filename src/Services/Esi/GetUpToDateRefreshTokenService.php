<?php

namespace Seatplus\Eveapi\Services\Esi;

use Illuminate\Support\Facades\Cache;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Exceptions\InvalidRefreshTokenException;
use Seatplus\Eveapi\Models\RefreshToken;

class GetUpToDateRefreshTokenService
{
    public function __construct(
        private ?UpdateRefreshTokenService $updateRefreshTokenService = null
    ) {
        $this->updateRefreshTokenService ??= new UpdateRefreshTokenService;
    }

    /**
     * @throws InvalidRefreshTokenException
     */
    public function get(RefreshToken $refresh_token): RefreshToken
    {
        $character_id = $refresh_token->character_id;

        return Cache::lock("get up to date refresh_token of character_id: {$character_id}", 10)
            ->block(30, function () use ($refresh_token) {
                $token = $refresh_token->refresh();

                if (carbon($token->expires_on)->gt(now()->addMinute())) {
                    return $token;
                }

                try {
                    return $this->updateRefreshTokenService->update($token);
                } catch (RequestFailedException $e) {
                    if ($e->getCode() === 400 || $e->getCode() === 401) {
                        throw new InvalidRefreshTokenException(
                            "Refresh token for character {$refresh_token->character_id} is invalid: {$e->getMessage()}",
                            $e->getCode(),
                            $e,
                        );
                    }

                    throw $e;
                }
            });
    }
}
