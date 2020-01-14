<?php


namespace Seatplus\Eveapi\Models\Universe;


use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Events\UniverseConstellationCreated;

class Constellation extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'constellation_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_constellations';

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => UniverseConstellationCreated::class,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'constellation_id' => 'integer',
        'region_id' => 'integer',
        'name' => 'string',
    ];

    public function region()
    {
        return $this->hasOne(Region::class, 'region_id', 'region_id');
    }



}
