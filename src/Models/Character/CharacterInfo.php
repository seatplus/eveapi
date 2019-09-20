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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'corporation_id' => 'integer'
    ];

    public function corporation()
    {

        return $this->belongsTo(CorporationInfo::class,'corporation_id', 'corporation_id');
    }


}
