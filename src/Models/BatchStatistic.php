<?php

namespace Seatplus\Eveapi\Models;

use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchStatistic extends Model
{
    use HasFactory;

    protected $table = 'batch_statistics';

    protected $guarded = [];

    protected $appends = [
        'duration',
    ];

    public function getDurationAttribute(): int
    {
        /** @var Carbon $finished_at */
        $finished_at = $this->finished_at;

        return $this->started_at->diffInSeconds($finished_at);
    }

    public static function createEntry(Batch $batch): self
    {
        $attributes = [
            'started_at' => now(),
            'batch_id' => $batch->id,
            'total_jobs' => $batch->totalJobs,
            'batch_name' => $batch->name,
        ];

        // get app env from config
        $env = config('app.env');

        // get horizon config
        $horizon_config = config("horizon.environments.{$env}.seatplus-workers");

        // convert array to string
        $queue_balancing_configuration = json_encode($horizon_config);

        // add to attributes
        $attributes['queue_balancing_configuration'] = $queue_balancing_configuration;

        return self::create($attributes);
    }

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
