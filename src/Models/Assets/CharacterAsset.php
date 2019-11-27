<?php


namespace Seatplus\Eveapi\Models\Assets;


use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Events\CharacterAssetUpdating;

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

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'updating' => CharacterAssetUpdating::class
    ];

}
