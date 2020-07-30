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

namespace Seatplus\Eveapi\Observers;

use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsLocationJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetObserver
{
    /**
     * @var \Seatplus\Eveapi\Models\Assets\CharacterAsset
     */
    private CharacterAsset $character_asset;

    /**
     * Handle the User "created" event.
     *
     * @param \Seatplus\Eveapi\Models\Assets\CharacterAsset $character_asset
     *
     * @return void
     */
    public function created(CharacterAsset $character_asset)
    {
        $this->character_asset = $character_asset;

        $this->handleTypes();
        $this->handleLocations();
    }

    /**
     * Handle the User "updating" event.
     *
     * @param \Seatplus\Eveapi\Models\Assets\CharacterAsset $character_asset
     *
     * @return void
     */
    public function updating(CharacterAsset $character_asset)
    {
        $this->character_asset = $character_asset;

        if ($character_asset->isDirty('location_id')) {
            $this->handleLocations();
        }
    }

    private function handleTypes()
    {
        if ($this->character_asset->type) {
            return;
        }

        ResolveUniverseTypesByTypeIdJob::dispatch($this->character_asset->type_id)->onQueue('high');
    }

    private function handleLocations()
    {
        if ($this->character_asset->location) {
            return;
        }

        $job_container = new JobContainer([
            'refresh_token' => RefreshToken::find($this->character_asset->character_id),
            'queue' => 'high',
        ]);

        CharacterAssetsLocationJob::dispatch($job_container)->onQueue('high');
    }
}
