<?php

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class BatchUpdate extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function batchable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getIsPendingAttribute()
    {
        return !is_null($this->started_at) && is_null($this->finished_at);
    }

    public function scopePending(Builder $query)
    {
        $query->whereNotNull('started_at')->whereNull('finished_at');
    }

    public function scopeCharacter(Builder $query)
    {
        $query->whereMorphedTo('batchable',CharacterInfo::class);
    }

}