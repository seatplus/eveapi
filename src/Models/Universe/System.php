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

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\SystemFactory;
use Seatplus\Eveapi\Events\UniverseSystemCreated;

class System extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return SystemFactory::new();
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $with = ['region'];

    /**
     * @var string
     */
    protected $primaryKey = 'system_id';

    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_systems';

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => UniverseSystemCreated::class,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'system_id' => 'integer',
        'constellation_id' => 'integer',
        'name' => 'string',
        'security_class' => 'string',
        'security_status' => 'double',
    ];

    public function constellation()
    {
        return $this->belongsTo(Constellation::class, 'constellation_id', 'constellation_id');
    }

    public function region()
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

    public function stations()
    {
        return $this->hasMany(Station::class, 'system_id', 'system_id');
    }

    public function structures()
    {
        return $this->hasMany(Structure::class, 'solar_system_id', 'system_id');
    }
}
