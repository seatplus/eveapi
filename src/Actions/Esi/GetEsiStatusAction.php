<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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

namespace Seatplus\Eveapi\Actions\Esi;

use Exception;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

class GetEsiStatusAction extends RetrieveFromEsiBase
{
    use RateLimitsEsiCalls;

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/ping';

    /**
     * @var string
     */
    protected $version = '';

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     * @throws \Throwable
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function execute(): string
    {
        if($this->isEsiRateLimited())
            return 'rate limited';

        // If recently a status request was made, return the cached result
        if(! is_null($cache = cache('esi-status')))
            return $cache['status'];

        $start = microtime(true);

        try {

            $status = $this->retrieve()->raw;

        } catch (Exception $exception) {

            $status = 'Request failed with: ' . $exception->getMessage();

        }

        $end = microtime(true) - $start;

        cache(['esi-status' => [
            'status' => $status,
            'request_time' => $end,
        ]], now()->addSeconds(10));

        return $status;

    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
