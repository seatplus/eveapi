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

use Illuminate\Support\Str;
use Seatplus\EsiClient\Configuration;
use Seatplus\EsiClient\DataTransferObjects\EsiAuthentication;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

class RetrieveEsiData
{
    use RateLimitsEsiCalls;

    protected EsiClient $client;

    protected EsiRequestContainer $request;

    private EsiClientSetup $esi_client;

    public function getClient(): EsiClient
    {
        if (! isset($this->client)) {
            if (is_null($this->request->refresh_token)) {
                $this->client = $this->esi_client->get();

                return $this->client;
            }

            // retrieve up-to-date token
            $refresh_token = $this->request->refresh_token;
            $refresh_token = $refresh_token->fresh();

            $authentication = new EsiAuthentication([
                'refresh_token' => $refresh_token->refresh_token,
                'access_token' => $refresh_token->getRawOriginal('token'),
                'token_expires' => $refresh_token->expires_on,
            ]);

            $this->client = $this->esi_client->get($authentication);
        }

        return $this->client;
    }

    public function setClient(EsiClient $client): void
    {
        $this->client = $client;
    }

    public function __construct()
    {
        $this->esi_client = app('esi-client');
    }

    /**
     * @throws RequestFailedException
     * @throws \Seatplus\EsiClient\Exceptions\EsiScopeAccessDeniedException
     */
    public function execute(EsiRequestContainer $request): EsiResponse
    {
        $this->request = $request;

        $this->buildClient();

        try {
            $result = $this->getClient()->invoke($this->request->method, $this->request->endpoint, $this->request->path_values);
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

        $this->logWarnings($result);

        $this->updateRefreshToken();

        return $result;
    }

    private function buildClient()
    {
        $this->getClient()->setVersion($this->request->version);
        $this->getClient()->setRequestBody($this->request->request_body);
        $this->getClient()->setQueryParameters($this->request->query_parameters);

        // Configure the page to get
        if (! is_null($this->request->page)) {
            $this->getClient()->setQueryParameters(['page' => $this->request->page]);
        }
    }

    private function logWarnings(EsiResponse $response)
    {
        $logger = Configuration::getInstance()->getLogger();

        if (! is_null($response->pages) && $this->request->page === null) {
            $logger->warning('Response contained pages but none was expected');
        }

        if (! is_null($this->request->page) && $response->pages === null) {
            $logger->warning('Expected a paged response but had none');
        }

        if (array_key_exists('Warning', $response->headers)) {
            $logger->warning('A response contained a warning: ' . $response->headers['Warning']);
        }
    }

    private function updateRefreshToken()
    {
        if (is_null($this->getClient()) || $this->request->isPublic()) {
            return;
        }

        $auth = $this->getClient()->getAuthentication();

        $refresh_token = $this->request->refresh_token;
        $refresh_token->token = $auth->access_token ?? '-';
        $refresh_token->expires_on = $auth->token_expires;

        $refresh_token->save();
    }

    private function handleException(RequestFailedException $exception)
    {
        // If error is in 4xx or 5xx range increase esi rate limit
        if (($exception->getOriginalException()->getCode() >= 400) && ($exception->getOriginalException()->getCode() <= 599)) {
            $this->incrementEsiRateLimit();
        }

        // If RateLimited directly raise the EsiRateLimit to 80
        if (Str::contains($exception->getErrorMessage(), 'This software has exceeded the error limit for ESI.')) {
            $this->incrementEsiRateLimit(80);
        }

        // Sometimes CCP does funny stuff, such as: issue tokens that are valid for to long.
        // invalidate the token
        if($exception->getOriginalException()->getCode() === 403 && $exception->getErrorMessage() === 'token expiry is too far in the future') {
            if (! is_null($this->request->refresh_token)) {
                $this->request->refresh_token->expires_on = carbon()->subMinutes(10);
                $this->request->refresh_token->save();
            }
        }

        // If the token can't login and we get an HTTP 400 together with
        // and error message stating that this is an invalid_token, remove
        // the token from SeAT plus.
        if ($exception->getOriginalException()->getCode() == 400 && in_array($exception->getErrorMessage(), [
            'invalid_token: The refresh token is expired.',
            'invalid_token: The refresh token does not match the client specified.',
            'invalid_grant: Invalid refresh token. Character grant missing/expired.',
            'invalid_grant: Invalid refresh token. Unable to migrate grant.',
            'invalid_grant: Invalid refresh token. Token missing/expired.',
        ])) {


            if (! is_null($this->request->refresh_token)) {

                $refresh_token = $this->request->refresh_token->refresh();

                // Try compensating for race conditions, only delete invalid tokens that have not been updated recently
                if(carbon($refresh_token->updated_at)->isBefore(carbon()->subMinutes()))
                    // Remove the invalid token
                    $refresh_token->delete();
            }
        }
    }

    /**
     * @param EsiRequestContainer $request
     */
    public function setRequest(EsiRequestContainer $request): void
    {
        $this->request = $request;
    }
}
