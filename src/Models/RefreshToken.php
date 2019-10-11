<?php

namespace Seatplus\Eveapi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Seatplus\Web\Models\User;

class RefreshToken extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $casts = [
        'scopes' => 'array',
    ];

    /**
     * @var array
     */
    protected $dates = ['expires_on', 'deleted_at'];

    /**
     * @var string
     */
    protected $primaryKey = 'character_id';

    /**
     * @var array
     */
    protected $fillable = ['character_id', 'refresh_token', 'scopes', 'expires_on', 'token'];

    /**
     * Only return a token value if it is not already
     * considered expired.
     *
     * @param $value
     *
     * @return mixed
     */
    public function getTokenAttribute($value)
    {

        if ($this->expires_on->gt(Carbon::now()))
            return $value;

        return null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {

        return $this->belongsTo(User::class, 'character_id', 'id');
    }
}
