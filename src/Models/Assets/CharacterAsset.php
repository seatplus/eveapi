<?php

namespace Seatplus\Eveapi\Models\Assets;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Events\CharacterAssetUpdating;
use Seatplus\Eveapi\Models\Universe\Names;

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
        'updating' => CharacterAssetUpdating::class,
    ];

    public function type()
    {
        return $this->hasOne(Names::class,'id', 'type_id');
    }

}
