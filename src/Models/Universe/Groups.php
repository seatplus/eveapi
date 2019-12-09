<?php


namespace Seatplus\Eveapi\Models\Universe;


use Illuminate\Database\Eloquent\Model;

class Groups extends Model
{

    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'group_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_groups';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'group_id' => 'integer',
        'category_id' => 'integer',
        'name' => 'string',
        'published' => 'boolean',
    ];

    public function types()
    {
        return $this->hasMany(Types::class, 'group_id', 'group_id');
    }
}
