<?php


namespace Seatplus\Eveapi\Models\Character;


use Illuminate\Database\Eloquent\Model;

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
        'last_pulled' => 'date'
    ];


}
