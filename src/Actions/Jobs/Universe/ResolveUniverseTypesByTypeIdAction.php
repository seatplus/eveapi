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

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\Seatplus\AddAndGetIdsFromCache;
use Seatplus\Eveapi\Models\Universe\Type;

class ResolveUniverseTypesByTypeIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    /**
     * @var array
     */
    private $path_values;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $type_ids;

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
        return '/universe/types/{type_id}/';
    }

    public function getVersion(): string
    {
        return 'v3';
    }

    public function execute(?int $type_id = null)
    {
        $this->type_ids = (new AddAndGetIdsFromCache('type_ids_to_resolve', $type_id))->execute();

        $this->type_ids->unique()->each(function ($type_id) {

            $this->setPathValues([
                'type_id' => $type_id,
            ]);

            $response = $this->retrieve();

            if ($response->isCachedLoad()) return;

            return Type::firstOrCreate(
                ['type_id' => $response->type_id],
                [
                    'group_id' => $response->group_id,
                    'name' => $response->name,
                    'description' => $response->description,
                    'published' => $response->published,

                    'capacity' => $response->optional('capacity'),
                    'graphic_id' => $response->optional('graphic_id'),
                    'icon_id' => $response->optional('icon_id'),
                    'market_group_id' => $response->optional('market_group_id'),
                    'mass' => $response->optional('mass'),
                    'packaged_volume' => $response->optional('packaged_volume'),
                    'portion_size' => $response->optional('portion_size'),
                    'radius' => $response->optional('radius'),
                    'volume' => $response->optional('volume'),
                ]
            );

        });

        // If execution was invoked with a specific type_id return the response
        if (! is_null($type_id))
            return Type::find($type_id);

        return null;

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
