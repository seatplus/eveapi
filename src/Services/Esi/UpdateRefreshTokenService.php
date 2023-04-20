<?php

namespace Seatplus\Eveapi\Services\Esi;

use Seatplus\EsiClient\DataTransferObjects\EsiAuthentication;
use Seatplus\EsiClient\Services\UpdateRefreshTokenService as EsiClientUpdateToken;
use Seatplus\Eveapi\Models\RefreshToken;

class UpdateRefreshTokenService
{
    private EsiClientUpdateToken $refreshTokenService;

    public static function make()
    {
        return new static();
    }

    public function update(RefreshToken $refreshToken): RefreshToken
    {
        $authentication = new EsiAuthentication(
            client_id: config('eveapi.config.esi.eve_client_id'),
            secret: config('eveapi.config.esi.eve_client_secret'),
            access_token: $refreshToken->getRawOriginal('token'),
            refresh_token: $refreshToken->refresh_token,
        );

        // Values are access_token // expires_in // token_type // refresh_token
        $token = $this->getRefreshTokenService()->getRefreshTokenResponse($authentication);

        return RefreshToken::updateOrCreate([
            'character_id' => $refreshToken->character_id,
        ], [
            'refresh_token' => data_get($token, 'refresh_token'),
            'token' => data_get($token, 'access_token'),
            'expires_on' => carbon()->addSeconds(data_get($token, 'expires_in')),
        ]);
    }

    public function getRefreshTokenService(): EsiClientUpdateToken
    {
        if (! isset($this->refreshTokenService)) {
            $this->setRefreshTokenService(new EsiClientUpdateToken);
        }

        return $this->refreshTokenService;
    }

    public function setRefreshTokenService(EsiClientUpdateToken $refreshTokenService): self
    {
        $this->refreshTokenService = $refreshTokenService;

        return $this;
    }
}
