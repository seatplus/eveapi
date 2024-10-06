<?php

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class BatchUpdate extends Model
{
    protected $guarded = [];

    public function batchable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getIsPendingAttribute(): bool
    {
        return ! is_null($this->started_at) && is_null($this->finished_at);
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNotNull('started_at')->whereNull('finished_at');
    }

    public function scopeCharacter(Builder $query): void
    {
        $query->whereMorphedTo('batchable', CharacterInfo::class);
    }
    #[\Override]
    protected function casts() : array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
