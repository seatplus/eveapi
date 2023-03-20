<?php

namespace Seatplus\Eveapi\Models;

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

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function getDurationAttribute()
    {
        return $this->finished_at->diffInSeconds($this->started_at);
    }

    public static function createEntry(Batch $batch)
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
        $horizon_config = config("horizon.environments.${env}.seatplus-workers");

        // convert array to string
        $queue_balancing_configuration = json_encode($horizon_config);

        // add to attributes
        $attributes['queue_balancing_configuration'] = $queue_balancing_configuration;

        return parent::create($attributes);
    }
}
