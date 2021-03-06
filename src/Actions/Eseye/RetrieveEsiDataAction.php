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

namespace Seatplus\Eveapi\Actions\Eseye;

use Illuminate\Support\Str;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

class RetrieveEsiDataAction
{
    use RateLimitsEsiCalls;

    protected $get_eseye_client_action;

    protected $client = null;

    protected $request;

    public function __construct()
    {
        $this->get_eseye_client_action = new GetEseyeClientAction();
    }

    /**
     * @param \Seatplus\Eveapi\Containers\EsiRequestContainer $this ->request
     *
     * @return \Seat\Eseye\Containers\EsiResponse
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function execute(EsiRequestContainer $request): EsiResponse
    {
        $this->request = $request;

        $client = $this->get_eseye_client_action->execute($this->request->refresh_token);

        $this->client = $client;
        $this->client->setVersion($this->request->version);
        $this->client->setBody($this->request->request_body);
        $this->client->setQueryString($this->request->query_string);

        // Configure the page to get
        if (! is_null($this->request->page)) {
            $this->client->page($this->request->page);
        }

        try {
            $result = $this->client->invoke($this->request->method, $this->request->endpoint, $this->request->path_values);
        } catch (RequestFailedException $exception) {
            $this->handleException($exception);

            // Rethrow the exception
            throw $exception;
        }

        // If this is a cached load, don't bother with any further
        // processing.
        if ($result->isCachedLoad()) {
            return $result;
        }

        // Perform error checking
        $this->getLogWarningAction()->setEseyeClient($this->client)->execute($result, $this->request->page);

        $this->updateRefreshToken();

        return $result;
    }

    private function getLogWarningAction(): LogWarningsAction
    {
        return new LogWarningsAction();
    }

    private function updateRefreshToken()
    {
        if (is_null($this->client) || $this->request->isPublic()) {
            return;
        }

        $auth = $this->client->getAuthentication();

        $refresh_token = $this->request->refresh_token;
        $refresh_token->token = $auth->access_token ?? '-';
        $refresh_token->expires_on = $auth->token_expires;

        $refresh_token->save();
    }

    private function handleException(RequestFailedException $exception)
    {
        // If error is in 4xx or 5xx range increase esi rate limit
        if (($exception->getEsiResponse()->getErrorCode() >= 400) && ($exception->getEsiResponse()->getErrorCode() <= 599)) {
            $this->incrementEsiRateLimit();
        }

        // If RateLimited directly raise the EsiRateLimit to 80
        if (Str::contains($exception->getEsiResponse()->error(), 'This software has exceeded the error limit for ESI.')) {
            $this->incrementEsiRateLimit(80);
        }

        // If the token can't login and we get an HTTP 400 together with
        // and error message stating that this is an invalid_token, remove
        // the token from SeAT plus.
        if ($exception->getEsiResponse()->getErrorCode() == 400 && in_array($exception->getEsiResponse()->error(), [
            'invalid_token: The refresh token is expired.',
            'invalid_token: The refresh token does not match the client specified.',
        ])) {

            // Remove the invalid token
            if (! is_null($this->request->refresh_token)) {
                $this->request->refresh_token->delete();
            }
        }
    }
}
