<?php

namespace Seatplus\Eveapi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Seatplus\Web\Models\User;

class EsiScopes extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'esi_scopes';

    /**
     * @var array
     */
    protected $casts = [
        'scopes' => 'array',
    ];

    /**
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    
}
