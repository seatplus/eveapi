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

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequestBody;

class ResolveUniverseTypeByIdJob extends EsiBase implements HasPathValuesInterface
{
    use HasPathValues;
    use HasRequestBody;

    public function __construct(private int $type_id)
    {
        parent::__construct(
            method: 'get',
            endpoint: '/universe/types/{type_id}/',
            version: 'v3',
        );

        $this->setPathValues([
            'type_id' => $type_id,
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            ...parent::middleware(),
        ];
    }

    public function tags(): array
    {
        return [
            'type',
            'information',
            sprintf('type_id:%s', $this->type_id),
        ];
    }

    public function executeJob(): void
    {
        $response = $this->retrieve();

        Type::firstOrCreate(
            ['type_id' => $response->type_id],
            [
                'group_id' => $response->group_id,
                'name' => $response->name,
                'description' => $response->description,
                'published' => $response->published,

                'capacity' => data_get($response, 'capacity'),
                'graphic_id' => data_get($response, 'graphic_id'),
                'icon_id' => data_get($response, 'icon_id'),
                'market_group_id' => data_get($response, 'market_group_id'),
                'mass' => data_get($response, 'mass'),
                'packaged_volume' => data_get($response, 'packaged_volume'),
                'portion_size' => data_get($response, 'portion_size'),
                'radius' => data_get($response, 'radius'),
                'volume' => data_get($response, 'volume'),
            ]
        );
    }
}
