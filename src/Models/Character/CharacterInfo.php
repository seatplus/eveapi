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

namespace Seatplus\Eveapi\Models\Character;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Applications;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterInfo extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    public $incrementing = false;

    /**
     * @var string
     */
    protected $primaryKey = 'character_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'corporation_id' => 'integer',
    ];

    public function refresh_token()
    {
        return $this->hasOne(RefreshToken::class, 'character_id', 'character_id');
    }

    public function characters()
    {
        // we need to return has many as corpoation and alliance do have many character affiliated
        return $this->hasMany(CharacterInfo::class, 'character_id');
    }

    public function corporation()
    {
        return $this->hasOneThrough(
            CorporationInfo::class,
            CharacterAffiliation::class,
            'character_id',
            'corporation_id',
            'character_id',
            'corporation_id'
        );
    }

    public function alliance()
    {
        return $this->hasOneThrough(
            AllianceInfo::class,
            CharacterAffiliation::class,
            'character_id',
            'alliance_id',
            'character_id',
            'alliance_id'
        );
    }

    public function roles()
    {
        return $this->hasOne(CharacterRole::class, 'character_id', 'character_id');
    }

    public function character_affiliation()
    {
        return $this->hasOne(CharacterAffiliation::class, 'character_id', 'character_id');
    }

    public function application()
    {
        return $this->hasOne(Applications::class, 'character_id', 'character_id');
    }

    public function getCorporationIdAttribute()
    {
        return optional($this->character_affiliation)->corporation_id;
    }

    public function getAllianceIdAttribute()
    {
        return optional($this->character_affiliation)->alliance_id;
    }
}
