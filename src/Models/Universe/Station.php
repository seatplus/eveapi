<?php

namespace Seatplus\Eveapi\Models\Universe;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Events\UniverseStationCreated;

class Station extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'station_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_stations';

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => UniverseStationCreated::class,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'station_id' => 'integer',
        'name' => 'string',
        'owner_id' => 'integer',
        'system_id' => 'integer',
        'type_id' => 'integer',
    ];

    public function location()
    {
        return $this->morphOne(Location::class, 'locatable');
    }

    public function type()
    {
        return $this->hasOne(Type::class, 'type_id', 'type_id');
    }

    public function system()
    {
        return $this->belongsTo(System::class, 'system_id', 'system_id');
    }
}
