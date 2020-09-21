<?php


namespace Seatplus\Eveapi\Models\Recruitment;


use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class Enlistments extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    public $incrementing = false;

    public function corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }

}
