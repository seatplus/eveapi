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

use Illuminate\Queue\InteractsWithQueue;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

abstract class RetrieveFromEsiBase implements RetrieveFromEsiInterface
{
    use RateLimitsEsiCalls;
    use InteractsWithQueue;

    /**
     * @var \Seatplus\Eveapi\Containers\EsiRequestContainer|null
     */
    private $esi_request_container;

    /**
     * @throws RequestFailedException
     */
    public function retrieve(?int $page = null): EsiResponse
    {
        $this->builldEsiRequestContainer($page);

        try {
            return RetrieveEsiData::execute($this->esi_request_container);
        } catch (RequestFailedException $exception) {
            if ($exception->getMessage() === 'Forbidden') {
                $this->fail($exception);
            }

            throw $exception;
        }
    }

    private function getBaseEsiReuestContainer(): EsiRequestContainer
    {
        return new EsiRequestContainer([
            'method' => $this->getMethod(),
            'version' => $this->getVersion(),
            'endpoint' => $this->getEndpoint(),
        ]);
    }

    private function builldEsiRequestContainer(?int $page)
    {
        $this->esi_request_container = $this->getBaseEsiReuestContainer();

        if ($this instanceof HasPathValuesInterface) {
            $this->esi_request_container->path_values = $this->getPathValues();
        }

        if ($this instanceof HasRequestBodyInterface) {
            $this->esi_request_container->request_body = $this->getRequestBody();
        }

        if ($this instanceof HasRequiredScopeInterface) {
            $this->esi_request_container->refresh_token = $this->getRefreshToken();
        }

        if ($this instanceof HasQueryStringInterface) {
            $this->esi_request_container->query_string = $this->getQueryString();
        }

        $this->esi_request_container->page = $page;
    }
}
