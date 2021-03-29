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

namespace Seatplus\Eveapi\Actions\Location;

use Seatplus\Eveapi\Esi\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingIdsCache;
use Seatplus\Eveapi\Models\Universe\Structure;

class CacheAllPublicStrucutresIdAction extends RetrieveFromEsiBase
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/universe/structures/';

    /**
     * @var string
     */
    protected $version = 'v1';

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function execute()
    {
        $public_structure_ids = collect($this->retrieve());

        // Get structure ids younger then a week
        $structure_ids_younger_then_a_week = Structure::where('updated_at', '>', carbon('now')->subWeek())->pluck('structure_id')->values();

        $ids_to_cache = $public_structure_ids->filter(function ($id) use ($structure_ids_younger_then_a_week) {

            // Remove younger then a week structure from ids to cache
            return ! in_array($id, $structure_ids_younger_then_a_week->toArray());
        });

        (new CreateOrUpdateMissingIdsCache('new_public_structure_ids', $ids_to_cache))->handle();
    }
}
