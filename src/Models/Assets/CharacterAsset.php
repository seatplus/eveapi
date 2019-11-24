<?php


namespace Seatplus\Eveapi\Models\Assets;


use Illuminate\Database\Eloquent\Model;

class CharacterAsset extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'item_id';

}
