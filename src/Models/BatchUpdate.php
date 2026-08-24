<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

/**
 * @property string|null $batch_id
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property bool $is_pending
 */
#[Unguarded]
class BatchUpdate extends Model
{
    /** @return MorphTo<Model, $this> */
    public function batchable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return Attribute<bool, never> */
    protected function isPending(): Attribute
    {
        return Attribute::make(get: fn () => ! is_null($this->started_at) && is_null($this->finished_at));
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNotNull('started_at')->whereNull('finished_at');
    }

    #[Scope]
    protected function character(Builder $query): void
    {
        $query->whereMorphedTo('batchable', CharacterInfo::class);
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
