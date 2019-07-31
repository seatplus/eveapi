<?php


namespace Seatplus\Eveapi\Models\Character;

use Illuminate\Database\Eloquent\Model;

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


}
