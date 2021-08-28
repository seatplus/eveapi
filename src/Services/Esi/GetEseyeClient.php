<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Services\Esi;

use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;
use Seatplus\Eveapi\Models\RefreshToken;

class GetEseyeClient
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
    public function execute(RefreshToken $refresh_token = null): Eseye
    {
        $this->client = app('esi-client');

        $this->refresh_token = $refresh_token;

        if (is_null($this->refresh_token)) {
            return $this->client = $this->client->get();
        }

        // retrieve up-to-date token
        $this->refresh_token = $this->refresh_token->fresh();

        return $this->client = $this->client->get(new EsiAuthentication([
            'refresh_token' => $this->refresh_token->refresh_token,
            'access_token' => $this->refresh_token->token,
            'token_expires' => $this->refresh_token->expires_on,
            'scopes' => $this->refresh_token->scopes,
        ]));
    }
}
