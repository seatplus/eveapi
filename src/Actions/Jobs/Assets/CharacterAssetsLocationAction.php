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

namespace Seatplus\Eveapi\Actions\Jobs\Assets;

use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetsLocationAction
{
    private $location_ids;

    public function __construct(
        /**
         * @var \Seatplus\Eveapi\Models\RefreshToken
         */
        private RefreshToken $refresh_token
    )
    {
    }

    /**
     * @return mixed
     */
    public function getLocationIds()
    {
        return $this->location_ids;
    }

    public function buildLocationIds()
    {
        $this->location_ids = CharacterAsset::where('character_id', $this->refresh_token->character_id)
            ->AssetsLocationIds()
            ->inRandomOrder()
            ->pluck('location_id')
            ->unique()
            ->values();

        return $this;
    }

    public function execute()
    {
        $this->location_ids->each(function ($location_id) {
            dispatch(new ResolveLocationJob($location_id, $this->refresh_token))->onQueue('default');
        });
    }
}
