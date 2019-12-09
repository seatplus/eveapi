<?php

namespace Seatplus\Eveapi\Models\Assets;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Events\CharacterAssetUpdating;
use Seatplus\Eveapi\Models\Universe\Names;
use Seatplus\Eveapi\Models\Universe\Types;

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
        return $this->hasOne(Types::class, 'type_id', 'type_id');
    }
}
