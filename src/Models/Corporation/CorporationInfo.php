<?php

namespace Seatplus\Eveapi\Models\Corporation;


use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class CorporationInfo extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'corporation_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'corporation_id' => 'integer',
        'alliance_id' => 'integer'
    ];

    public function characters()
    {

        return $this->hasMany(CharacterInfo::class, 'corporation_id', 'corporation_id');
    }

}