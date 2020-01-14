<?php


namespace Seatplus\Eveapi\Models\Universe;


use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Events\UniverseSystemCreated;

class System extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'system_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_systems';

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => UniverseSystemCreated::class,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'system_id' => 'integer',
        'constellation_id' => 'integer',
        'name' => 'string',
        'security_class' => 'string',
        'security_status' => 'double',
    ];

    public function constellation()
    {
        return $this->belongsTo(Constellation::class, 'constellation_id', 'constellation_id');
    }

    public function stations()
    {
        return $this->hasMany(Station::class, 'system_id', 'system_id');
    }

    public function structures()
    {
        return $this->hasMany(Structure::class, 'system_id', 'system_id');
    }



}
