<?php


namespace Seatplus\Eveapi\Models\Universe;


use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{

    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'category_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_categories';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'category_id' => 'integer',
        'name' => 'string',
        'published' => 'boolean',
    ];

    public function types()
    {
        return $this->hasMany(Groups::class, 'group_id', 'group_id');
    }
}
