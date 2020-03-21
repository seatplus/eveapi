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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class CacheMissingTypeIdsAction
{
    public function execute(): Collection
    {
        $unknown_type_ids = CharacterAsset::whereDoesntHave('type')->pluck('type_id')->unique()->values();

        $unknown_type_ids = $unknown_type_ids->merge($this->getMissingTypeIdsFromLocations());

        (new CreateOrUpdateMissingIdsCache('type_ids_to_resolve', $unknown_type_ids))->handle();

        return $unknown_type_ids;
    }

    private function getMissingTypeIdsFromLocations(): Collection
    {
        return Location::whereHasMorph(
            'locatable',
            [Station::class, Structure::class],
            function (Builder $query) {
                $query->whereDoesntHave('type')->addSelect('type_id');
            }
        )->with('locatable')->get()->map(function ($location) {
            return $location->locatable->type_id;
        });
    }
}
