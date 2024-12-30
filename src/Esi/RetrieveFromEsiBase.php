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
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;

abstract class RetrieveFromEsiBase implements RetrieveFromEsiInterface
{
    use InteractsWithQueue;

    private EsiRequestContainer $esi_request_container;

    /**
     * @throws RequestFailedException
     */
    public function retrieve(?int $page = null): EsiResponse
    {
        $this->builldEsiRequestContainer($page);

        try {
            return RetrieveEsiData::execute($this->esi_request_container);
        } catch (RequestFailedException $exception) {
            $this->handleException($exception);

            throw $exception;
        }
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

        // if original exception is ClientException, we can safely assume that the request was invalid
        if ($original_exception instanceof ClientException) {
            $this->fail($exception);
        }

        // if original exception is ServerException, we can safely assume that the request was valid
        // but the server failed so we can release the job back into the queue
        if ($original_exception instanceof ServerException) {
            $this->release(60);
        }
    }
}
