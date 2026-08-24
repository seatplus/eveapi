<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models\Recruitment;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Application;

#[Unguarded]
class ApplicationLogs extends Model
{
    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return MorphTo<Model, $this> */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}
