<?php

namespace Seatplus\Eveapi\Models\Character;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;

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

        return $this->belongsTo(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }

    public function alliance()
    {

        return $this->belongsTo(AllianceInfo::class, 'alliance_id', 'alliance_id');
    }
}
