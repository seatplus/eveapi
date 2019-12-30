<?php

namespace Seatplus\Eveapi\Models\Universe;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'location_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_locations';

    public function locatable()
    {
        return $this->morphTo();
    }
}
