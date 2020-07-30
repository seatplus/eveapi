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

class CreateOrUpdateMissingIdsCache
{
    /**
     * @var \Illuminate\Support\Collection
     */
    public $ids;

    /**
     * @var string
     */
    private $cache_string;

    public function __construct(string $cache_string, Collection $ids)
    {
        $this->ids = $ids;
        $this->cache_string = $cache_string;
    }

    public function handle()
    {
        if (Cache::has($this->cache_string)) {
            $pending_ids = Cache::pull($this->cache_string);

            $this->ids = $this->ids->merge($pending_ids)->flatten();
        }

        Cache::put($this->cache_string, $this->ids->map(function ($id) {
            return (int) $id;
        })->values()->all());
    }
}
