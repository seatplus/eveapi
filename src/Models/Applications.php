<?php


namespace Seatplus\Eveapi\Models;


use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class Applications extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'corporation_id' => 'integer',
    ];

    public function corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }

    public function character()
    {
        return $this->hasOne(CharacterInfo::class, 'character_id', 'character_id');
    }

}
