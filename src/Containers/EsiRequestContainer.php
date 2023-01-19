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

namespace Seatplus\Eveapi\Containers;

use Seatplus\Eveapi\Models\RefreshToken;

class EsiRequestContainer
{
    public function __construct(
        public string $method,
        public string $version,
        public string $endpoint,
        public ?int $page = null,
        public ?RefreshToken $refresh_token = null,
        public array $path_values = [],
        public array $request_body = [],
        public array $query_parameters = [],
    ) {
    }

    public function isPublic(): bool
    {
        return is_null($this->refresh_token);
    }

    public function setRequestBody(array $request_body)
    {
        $this->request_body = $request_body;

        return $this;
    }
}
