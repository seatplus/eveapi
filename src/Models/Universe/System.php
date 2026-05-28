<?php

declare(strict_types=1);

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

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Seatplus\Eveapi\Events\UniverseSystemCreated;

#[Unguarded] #[WithoutIncrementing]

class System extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $with = ['region'];

    /**
     * @var string
     */
    protected $primaryKey = 'system_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_systems';

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => UniverseSystemCreated::class,
    ];

    public function constellation(): BelongsTo
    {
        return $this->belongsTo(Constellation::class, 'constellation_id', 'constellation_id');
    }

    public function region(): HasOneThrough
    {
        return $this->hasOneThrough(
            Region::class,
            Constellation::class,
            'constellation_id',
            'region_id',
            'constellation_id',
            'region_id'
        );
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'system_id', 'system_id');
    }

    public function structures(): HasMany
    {
        return $this->hasMany(Structure::class, 'solar_system_id', 'system_id');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'system_id' => 'integer',
            'constellation_id' => 'integer',
            'name' => 'string',
            'security_class' => 'string',
            'security_status' => 'double',
        ];
    }
}
