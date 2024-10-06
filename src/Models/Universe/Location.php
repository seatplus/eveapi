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

namespace Seatplus\Eveapi\Models\Universe;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\LocationWatchListInterface;

class Location extends Model implements LocationWatchListInterface
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @var string
     */
    protected $primaryKey = 'location_id';

    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_locations';

    public function locatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'location_id', 'location_id');
    }

    #[\Override]
    public function scopeFilterByRegionIds(Builder $query, int|array $regions): Builder
    {
        $region_ids = is_array($regions) ? $regions : [$regions];

        return $query->whereHas('locatable.system.constellation', function (Builder $query) use ($region_ids) {
            $query->whereIn('region_id', $region_ids);
        });
    }

    #[\Override]
    public function scopeFilterBySystemIds(Builder $query, int|array $systems): Builder
    {
        $system_ids = is_array($systems) ? $systems : [$systems];

        return $query->whereHas('locatable.system', function (Builder $query) use ($system_ids) {
            $query->whereIn('system_id', $system_ids);
        });
    }
}
