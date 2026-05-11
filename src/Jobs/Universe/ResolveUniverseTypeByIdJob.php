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

use Seatplus\Eveapi\DataTransferObjects\Responses\Universe\TypeResponse;
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
     */
    #[\Override]
    public function middleware(): array
    {
        return [
            ...parent::middleware(),
        ];
    }

    #[\Override]
    public function tags(): array
    {
        return [
            'type',
            'information',
            sprintf('type_id:%s', $this->type_id),
        ];
    }

    #[\Override]
    public function executeJob(): void
    {
        $response = $this->retrieve();

        $data = TypeResponse::from($response->data);

        Type::firstOrCreate(
            ['type_id' => $data->type_id],
            [
                'group_id' => $data->group_id,
                'name' => $data->name,
                'description' => $data->description,
                'published' => $data->published,
                'capacity' => $data->capacity,
                'graphic_id' => $data->graphic_id,
                'icon_id' => $data->icon_id,
                'market_group_id' => $data->market_group_id,
                'mass' => $data->mass,
                'packaged_volume' => $data->packaged_volume,
                'portion_size' => $data->portion_size,
                'radius' => $data->radius,
                'volume' => $data->volume,
            ]
        );
    }
}
