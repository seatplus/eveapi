<?php

namespace Seatplus\Eveapi\Models\Character;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Category;

class CharacterInfo extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

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

    public function getCorporationIdAttribute()
    {

        return $this->character_affiliation->corporation_id;
    }

    public function getAllianceIdAttribute()
    {

        return $this->character_affiliation->alliance_id;
    }
}
