<?php


namespace Seatplus\Eveapi\Models\Universe;


use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Events\UniverseConstellationCreated;

class Region extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'region_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_regions';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'region_id' => 'integer',
        'name' => 'string',
        'description' => 'string'
    ];



}
