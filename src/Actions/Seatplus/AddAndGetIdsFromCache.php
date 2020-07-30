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

namespace Seatplus\Eveapi\Actions\Seatplus;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AddAndGetIdsFromCache
{
    /**
     * @var string
     */
    private $cache_key;

    /**
     * @var int
     */
    private $id_to_add;

    private $ids_to_return;

    public function __construct(string $cache_key, ?int $id_to_add = null)
    {
        $this->cache_key = $cache_key;
        $this->id_to_add = $id_to_add;
        $this->ids_to_return = collect();
    }

    public function execute(): Collection
    {
        if (! is_null($this->id_to_add)) {
            $this->ids_to_return->push($this->id_to_add);
        }

        if (Cache::has($this->cache_key)) {
            $cached_group_ids = Cache::pull($this->cache_key);

            collect($cached_group_ids)->each(function ($cached_group_id) {
                $this->ids_to_return->push($cached_group_id);
            });
        }

        return $this->ids_to_return;
    }
}
