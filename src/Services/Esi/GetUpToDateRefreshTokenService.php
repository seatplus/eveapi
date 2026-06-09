<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\Esi;

use Illuminate\Support\Facades\Cache;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Exceptions\InvalidRefreshTokenException;
use Seatplus\Eveapi\Models\RefreshToken;

class GetUpToDateRefreshTokenService
{
    public function __construct(
        private readonly UpdateRefreshTokenService $updateRefreshTokenService = new UpdateRefreshTokenService,
    ) {}

    /**
     * @throws InvalidRefreshTokenException
     */
    public function get(RefreshToken $refreshToken): RefreshToken
    {
        $characterId = $refreshToken->character_id;

        return Cache::lock("get up to date refresh_token of character_id: {$characterId}", 10)
            ->block(30, function () use ($refreshToken) {
                $token = $refreshToken->refresh();

                if (carbon($token->expires_on)->gt(now()->addMinute())) {
                    return $token;
                }

                try {
                    return $this->updateRefreshTokenService->update($token);
                } catch (RequestFailedException $e) {
                    if ($e->getCode() === 400 || $e->getCode() === 401) {
                        throw new InvalidRefreshTokenException(
                            "Refresh token for character {$refreshToken->character_id} is invalid: {$e->getMessage()}",
                            $e->getCode(),
                            $e,
                        );
                    }

                    throw $e;
                }
            });
    }
}
