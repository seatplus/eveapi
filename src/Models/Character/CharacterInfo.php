<?php


namespace Seatplus\Eveapi\Models\Character;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

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

    public function corporation()
    {

        return $this->belongsTo(CorporationInfo::class,'corporation_id', 'corporation_id');
    }


}
