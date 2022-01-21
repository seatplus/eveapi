<?php

namespace Seatplus\Eveapi\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\Models\Application;

class ApplicationLogs extends Model
{
    protected $guarded = [];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function causer()
    {
        return $this->morphTo();
    }
}
