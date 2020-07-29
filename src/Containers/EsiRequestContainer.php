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

namespace Seatplus\Eveapi\Containers;

use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;

class EsiRequestContainer
{
    protected $data = [
        'method' => '',
        'version' => '',
        'endpoint' => '',
        'refresh_token' => null,
        'path_values' => [],
        'page' => null,
        'request_body' => [],
        'query_string' => [],
    ];

    public function __construct(array $data = null)
    {
        if (! is_null($data)) {
            foreach ($data as $key => $value) {
                if (! array_key_exists($key, $this->data)) {
                    throw new InvalidContainerDataException(
                        'Key ' . $key . ' is not valid for this container'
                    );
                }

                $this->$key = $value;
            }
        }
    }

    public function __set($key, $value): void
    {
        if (array_key_exists($key, $this->data)) {
            $this->data[$key] = $value;
        }
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return '';
    }

    public function isPublic(): bool
    {
        return is_null($this->data['refresh_token']);
    }

    public function setRequestBody(array $request_body)
    {
        $this->request_body = $request_body;

        return $this;
    }
}
