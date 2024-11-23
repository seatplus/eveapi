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

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Seatplus\EsiClient\DataTransferObjects\EsiAuthentication;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\EsiConfiguration;
use Seatplus\EsiClient\Exceptions\EsiScopeAccessDeniedException;
use Seatplus\EsiClient\Exceptions\InvalidAuthenticationException;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\EsiClient\Exceptions\UriDataMissingException;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Models\RefreshToken;

class RetrieveEsiData
{
    /**
     * @throws RequestFailedException
     */
    public function __construct(
        private readonly string                 $method = '',
        private readonly string                 $endpoint = '',
        private readonly string                 $version = '',
        private readonly array                  $path_values = [],
        private array                           $query_parameters = [],
        private readonly ?array                 $request_body = [],
        private ?RefreshToken                   $refresh_token = null,
        private readonly ?int                   $page = null,
        private ?EsiClient                      $client = null,
        private ?GetUpToDateRefreshTokenService $getUpToDateRefreshTokenService = null
    )
    {
        $this->client = $client ?? $this->buildClient();

        if($page) {
            $this->query_parameters['page'] = $page;
        }

    }

    /**
     * @param EsiRequestContainer $container
     * @return EsiResponse
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidAuthenticationException
     * @throws RequestFailedException
     * @throws UriDataMissingException
     * @throws \Throwable
     */
    public static function execute(
        EsiRequestContainer $container,
        ?EsiClient $client = null
    ): EsiResponse
    {
        return (new self(
            method: $container->method,
            endpoint: $container->endpoint,
            version: $container->version,
            path_values: $container->path_values,
            query_parameters: $container->query_parameters,
            request_body: $container->request_body,
            refresh_token: $container->refresh_token,
            page: $container->page,
            client: $client
        ))->executeInstance();
    }

    /**
     * @return EsiResponse
     * @throws EsiScopeAccessDeniedException
     * @throws InvalidAuthenticationException
     * @throws RequestFailedException
     * @throws UriDataMissingException
     * @throws \Throwable
     */
    public function executeInstance(): EsiResponse
    {

        try {
            $result = $this->client->invoke(
                method: $this->method,
                uri_original: $this->endpoint,
                uri_data: $this->path_values,
                version: $this->version,
                query_parameters: $this->query_parameters,
                request_body: $this->request_body
            );
        } catch (RequestFailedException $exception) {
            $this->handleException($exception);
            // Rethrow the exception
            throw $exception;
        } catch (EsiScopeAccessDeniedException | InvalidAuthenticationException | UriDataMissingException | \Throwable $exception) {

            $logger = EsiConfiguration::getInstance()->getLogger();
            $logger->error($exception->getMessage());

            throw $exception;
        }

        // If this is a cached load, don't bother with any further
        // processing.
        if ($result->isCachedLoad()) {
            return $result;
        }

        $this->logWarnings($result);

        // Update the refresh token if we have one
        $this->refresh_token?->save();

        return $result;
    }

    private function logWarnings(EsiResponse $response): void
    {
        $logger = EsiConfiguration::getInstance()->getLogger();

        if ($response->pages !== null && $this->page === null) {
            $logger->warning('Response contained pages but none was expected');
        }

        if ($response->pages === null && $this->page !== null) {
            $logger->warning('Expected a paged response but had none');
        }

        if (isset($response->parsed_headers['Warning'])) {
            $logger->warning("Response contained a warning: {$response->parsed_headers['Warning']}");
        }
    }

    private function handleException(RequestFailedException $exception): void
    {

        // If RateLimited directly raise the EsiRateLimit to 80
        if (Str::contains($exception->getErrorMessage(), 'This software has exceeded the error limit for ESI.')) {
            Redis::incrby('esiratelimit', 80);
        }

        // return if no refresh token is available
        if(! $this->refresh_token) {
            return;
        }

        // Sometimes CCP does funny stuff, such as: issue tokens that are valid for to long.
        // invalidate the token
        if ($exception->getOriginalException()->getCode() === 403 && $exception->getErrorMessage() === 'token expiry is too far in the future') {
            $this->refresh_token->expires_on = carbon()->subMinutes(10);
            $this->refresh_token->save();
        }

        // If the token can't log in and we get an HTTP 400 together with
        // and error message stating that this is an invalid_token, remove
        // the token from SeAT plus.
        if ($exception->getOriginalException()->getCode() == 400 && in_array($exception->getErrorMessage(), [
            'invalid_token: The refresh token is expired.',
            'invalid_token: The refresh token does not match the client specified.',
            'invalid_grant: Invalid refresh token. Character grant missing/expired.',
            'invalid_grant: Invalid refresh token. Unable to migrate grant.',
            'invalid_grant: Invalid refresh token. Token missing/expired.',
        ])) {
            $refresh_token = $this->refresh_token->refresh();

            // Try compensating for race conditions, only delete invalid tokens that have not been updated recently
            if (carbon($refresh_token->updated_at)->isBefore(carbon()->subMinutes())) {
                // Remove the invalid token
                $refresh_token->delete();
            }
        }
    }

    /**
     * @throws RequestFailedException
     */
    private function buildClient(): EsiClient
    {
        $esi_client = new EsiClientSetup();

        if (is_null($this->refresh_token)) {
            return $esi_client->get();
        }

        $this->getUpToDateRefreshTokenService = $this->getUpToDateRefreshTokenService ?? new GetUpToDateRefreshTokenService();

        try {
            $this->refresh_token = ($this->getUpToDateRefreshTokenService)($this->refresh_token);
        } catch (RequestFailedException $e) {
            $this->handleException($e);
            throw $e;
        }

        $authentication = new EsiAuthentication(
            access_token: $this->refresh_token->getRawOriginal('token'),
            refresh_token: $this->refresh_token->refresh_token,
            token_expires: $this->refresh_token->expires_on,
        );

        return $esi_client->get($authentication);
    }
}
