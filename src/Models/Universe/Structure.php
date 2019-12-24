<?php


namespace Seatplus\Eveapi\Models\Universe;


use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'structure_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_structures';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'structure_id' => 'integer',
        'name' => 'string',
        'owner_id' => 'integer',
        'solar_system_id' => 'integer',
        'type_id' => 'integer'
    ];

    public function location()
    {
        return $this->morphOne(Location::class, 'locatable');
    }

    public function type()
    {
        return $this->hasOne(Type::class, 'type_id', 'type_id');
    }

}
