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

    public function characters()
    {

        return $this->hasMany(CharacterInfo::class, 'corporation_id', 'corporation_id');
    }

}