<?php

namespace Seatplus\Eveapi\Models\Assets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Events\CharacterAssetUpdating;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;

class CharacterAsset extends Model
{
    const ASSET_SAFETY = 2004;

    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'item_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'type_id' => 'integer'
    ];

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
        return $this->hasOne(Type::class, 'type_id', 'type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function container()
    {
        return $this->belongsTo(CharacterAsset::class, 'item_id', 'location_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function content()
    {
        return $this->hasMany(CharacterAsset::class, 'location_id', 'item_id');
    }

    public function location()
    {
        return $this->hasOne(Location::class, 'location_id', 'location_id');
    }


    public function scopeAssetsLocationIds(Builder $query) : Builder
    {
        return $query->whereDoesntHave('content')
            ->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
            ->select('location_id');
    }

    public function scopeWithoutAssetSafety(Builder $query) : Builder
    {
        return $query->where('location_id', '<>', self::ASSET_SAFETY);
    }
}
