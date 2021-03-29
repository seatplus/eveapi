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

use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\Seatplus\AddAndGetIdsFromCache;
use Seatplus\Eveapi\Models\Universe\Group;

class ResolveUniverseGroupsByGroupIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    /**
     * @var array
     */
    private $path_values;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $group_ids;

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/groups/{group_id}/';
    }

    public function getVersion(): string
    {
        return 'v1';
    }

    public function execute(?int $group_id = null)
    {
        $this->group_ids = (new AddAndGetIdsFromCache('group_ids_to_resolve', $group_id))->execute();

        $this->group_ids->unique()->each(function ($group_id) {
            $this->setPathValues([
                'group_id' => $group_id,
            ]);

            $response = $this->retrieve();

            if ($response->isCachedLoad()) {
                return;
            }

            return Group::firstOrCreate(
                ['group_id' => $response->group_id],
                [
                    'category_id' => $response->category_id,
                    'name' => $response->name,
                    'published' => $response->published,
                ]
            );
        });

        // If execution was invoked with a specific type_id return the response
        if (! is_null($group_id)) {
            return Group::find($group_id);
        }

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
