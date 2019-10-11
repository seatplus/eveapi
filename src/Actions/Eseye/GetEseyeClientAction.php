<?php

namespace Seatplus\Eveapi\Actions\Eseye;

use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;
use Seatplus\Eveapi\Models\RefreshToken;

class GetEseyeClientAction
{
    private $client;

    private $refresh_token;

    public function __construct()
    {
        $this->client = app('esi-client');
    }

    /**
     * @param \Seatplus\Eveapi\Models\RefreshToken|null $refresh_token
     *
     * @return \Seat\Eseye\Eseye
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function execute(RefreshToken $refresh_token = null) : Eseye
    {
        $this->client = app('esi-client');

        $this->refresh_token = $refresh_token;

        if (is_null($this->refresh_token))
            return $this->client = $this->client->get();

        // retrieve up-to-date token
        $this->refresh_token = $this->refresh_token->fresh();

        return $this->client = $this->client->get(new EsiAuthentication([
            'refresh_token' => $this->refresh_token->refresh_token,
            'access_token'  => $this->refresh_token->token,
            'token_expires' => $this->refresh_token->expires_on,
            'scopes'        => $this->refresh_token->scopes,
        ]));

    }
}
