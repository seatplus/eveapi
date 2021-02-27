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

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\System;

class ResolveUniverseSystemsBySystemIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    /**
     * @var array|null
     */
    private $path_values;

    /**
     * @param mixed $request_body
     */
    public function setRequestBody(array $request_body): void
    {
        $this->request_body = $request_body;
    }

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/systems/{system_id}/';
    }

    public function getVersion(): string
    {
        return 'v4';
    }

    public function execute(int $system_id)
    {
        $this->setPathValues([
            'system_id' => $system_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        return System::firstOrCreate(
            ['system_id' => $system_id],
            [
                'constellation_id' => $response->constellation_id,
                'name' => $response->name,
                'security_status' => $response->security_status,

                'security_class' => $response->optional('security_class'),
            ]
        );
    }

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }
}
