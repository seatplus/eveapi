<?php

namespace Seatplus\Eveapi\Models\Character;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class CharacterAffiliation extends Model
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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'character_affiliations';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'corporation_id' => 'integer',
        'alliance_id' => 'integer',
        'faction_id' => 'integer',
        'last_pulled' => 'datetime',
    ];

    public function alliance()
    {

        return $this->belongsTo(AllianceInfo::class, 'alliance_id', 'alliance_id');
    }

    public function corporation()
    {

        return $this->belongsTo(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }

    public function character()
    {

        return $this->hasOne(CharacterInfo::class, 'character_id', 'character_id');
    }
}
