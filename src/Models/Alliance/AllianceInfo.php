<?php

namespace Seatplus\Eveapi\Models\Alliance;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class AllianceInfo extends Model
{

    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'alliance_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'alliance_id' => 'integer',
    ];

    public function corporations()
    {

        return $this->hasMany(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }
}
