<?php

namespace Seatplus\Eveapi\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Application;

class ApplicationLogs extends Model
{
    protected $guarded = [];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}
