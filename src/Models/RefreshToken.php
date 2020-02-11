<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Seatplus\Auth\Models\User;
use Seatplus\Eveapi\Events\RefreshTokenSaved;

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

    protected $dispatchesEvents = [
        'saved' => RefreshTokenSaved::class,
    ];

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

    public function hasScope(string $scope): bool
    {

        return in_array($scope, $this->scopes);
    }
}
