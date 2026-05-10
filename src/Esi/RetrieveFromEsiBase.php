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

namespace Seatplus\Eveapi\Esi;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Queue\InteractsWithQueue;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Exceptions\EsiErrorLimitedException;
use Seatplus\EsiClient\Exceptions\EsiRateLimitedException;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Exceptions\EsiJobReleasedException;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;

abstract class RetrieveFromEsiBase implements RetrieveFromEsiInterface
{
    use InteractsWithQueue;

    private EsiRequestContainer $esi_request_container;

    /**
     * @throws RequestFailedException
     * @throws EsiJobReleasedException
     */
    public function retrieve(?int $page = null): EsiResponse
    {
        $this->builldEsiRequestContainer($page);

        try {
            $response = RetrieveEsiData::execute($this->esi_request_container);
        } catch (EsiRateLimitedException|EsiErrorLimitedException $exception) {
            $this->release($exception->retryAfter);
            throw new EsiJobReleasedException($exception->getMessage(), $exception->getCode(), $exception);
        } catch (RequestFailedException $exception) {
            $this->handleException($exception);

            throw $exception;
        }

        // Record rate-limit state for the proactive middleware when headers are present.
        if ($response->ratelimitGroup !== null
            && $response->ratelimitRemaining !== null
            && $response->ratelimitLimit !== null
            && $response->ratelimitWindowSeconds !== null
        ) {
            EsiProactiveRateLimitMiddleware::recordResponse(
                group: $response->ratelimitGroup,
                remaining: $response->ratelimitRemaining,
                limit: $response->ratelimitLimit,
                windowSeconds: $response->ratelimitWindowSeconds,
            );
        }

        return $response;
    }

    private function getBaseEsiReuestContainer(): EsiRequestContainer
    {
        return new EsiRequestContainer(
            method: $this->getMethod(),
            version: $this->getVersion(),
            endpoint: $this->getEndpoint(),
        );
    }

    private function builldEsiRequestContainer(?int $page): void
    {
        $this->esi_request_container = $this->getBaseEsiReuestContainer();

        try {
            if ($this instanceof HasRequiredScopeInterface) {
                $this->esi_request_container->refresh_token = $this->getRefreshToken();
            }

            if ($this instanceof HasPathValuesInterface) {
                $this->esi_request_container->path_values = $this->getPathValues();
            }

            if ($this instanceof HasRequestBodyInterface) {
                $this->esi_request_container->request_body = $this->getRequestBody();
            }

            if ($this instanceof HasQueryParametersInterface) {
                $this->esi_request_container->query_parameters = $this->getQueryParameters();
            }
        } catch (Exception $exception) {
            // fail job
            $this->fail($exception);
        }

        $this->esi_request_container->page = $page;
    }

    private function handleException(RequestFailedException $exception): void
    {
        $original_exception = $exception->getOriginalException();

        // ServerException (5xx) — release for retry, respect Retry-After if present
        if ($original_exception instanceof ServerException) {
            $retryAfter = (int) ($original_exception->getResponse()->getHeader('Retry-After')[0] ?? 60);
            $this->release(max(60, $retryAfter));

            return;
        }

        // Any other ClientException (4xx except 429/420 which are caught upstream) — fail permanently
        if ($original_exception instanceof ClientException) {
            $this->fail($exception);
        }
    }
}
