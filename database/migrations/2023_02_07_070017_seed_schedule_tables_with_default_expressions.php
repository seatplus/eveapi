<?php

use Illuminate\Database\Migrations\Migration;
use Seatplus\Eveapi\Models\Schedules;

return new class extends Migration
{
    public function up()
    {

        $jobs = [
            // schedule UpdateCharacter to run every minute
            \Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter::class => '* * * * *',
            // schedule UpdateCorporation to run every minute
            \Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation::class => '* * * * *',
            // schedule MaintenanceJob to run every day at 00:00
            \Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob::class => '0 0 * * *',
        ];
        // if the schedule is not in the database, create it
        foreach ($jobs as $job => $schedule) {
            Schedules::query()->firstOrCreate([
                'job' => $job,
            ], [
                'expression' => $schedule,
            ]);
        }
    }
};
