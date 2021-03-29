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

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Esi\HasRequestBodyInterface;
use Seatplus\Eveapi\Esi\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\Name;

class NamesAction extends RetrieveFromEsiBase implements HasRequestBodyInterface
{
    protected $request_body;

    /**
     * @param mixed $request_body
     */
    public function setRequestBody(array $request_body): void
    {
        $this->request_body = $request_body;
    }

    public function getMethod(): string
    {
        return 'post';
    }

    public function getEndpoint(): string
    {
        return '/universe/names/';
    }

    public function getVersion(): string
    {
        return 'v3';
    }

    public function getRequestBody(): array
    {
        return $this->request_body;
    }

    public function execute(Collection $type_ids)
    {
        $type_ids->unique()->chunk(1000)->each(function (Collection $chunk) {
            $this->setRequestBody($chunk->values()->all());

            $response = $this->retrieve();

            if ($response->isCachedLoad()) {
                return;
            }

            collect($response)->map(function ($result) {
                return Name::updateOrCreate(
                    ['id' => $result->id],
                    ['name' => $result->name, 'category' => $result->category]
                );
            });
        });

        return null;
    }
}
