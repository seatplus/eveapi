<?php

namespace Seatplus\Eveapi\Models\Universe;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{

    /**
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * @var string
     */
    protected $primaryKey = 'type_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_types';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'type_id' => 'integer',
        'group_id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'published' => 'boolean',
    ];

    public function group()
    {
        return $this->hasOne(Group::class, 'group_id', 'group_id');
    }

    public function category()
    {
        return $this->hasOneThrough(
            Category::class,
            Group::class,
            'group_id',
            'category_id',
            'group_id',
            'category_id'
        );
    }
}
