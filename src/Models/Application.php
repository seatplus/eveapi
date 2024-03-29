<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Seatplus\Auth\Models\User;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Recruitment\ApplicationLogs;
use Seatplus\Eveapi\Models\Recruitment\Enlistments;

class Application extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted()
    {
        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Str::uuid());
        });
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'corporation_id' => 'integer',
    ];

    public function corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }

    public function enlistment()
    {
        return $this->belongsTo(Enlistments::class, 'corporation_id', 'corporation_id');
    }

    public function applicationable()
    {
        return $this->morphTo();
    }

    public function log_entries()
    {
        return $this->hasMany(ApplicationLogs::class);
    }

    public function getDecisionCountAttribute()
    {
        return $this->log_entries()->where('type', 'decision')->count();
    }

    public function scopeOfCorporation(Builder $query, int|array $corporation): Builder
    {
        $corporation_ids = is_int($corporation) ? [$corporation] : $corporation;

        return $query->whereIn('corporation_id', $corporation_ids)
            ->with([
                'applicationable' => fn (MorphTo $morph_to) => $morph_to->morphWith([
                    User::class => ['characters.refresh_token', 'main_character', 'characters.application.corporation.ssoScopes', 'characters.application.corporation.alliance.ssoScopes'],
                    CharacterInfo::class => ['refresh_token', 'application.corporation.ssoScopes', 'application.corporation.alliance.ssoScopes'],
                ]),
            ]);
    }
}
