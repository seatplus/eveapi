<?php

use Illuminate\Database\Migrations\Migration;
use Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Models\Schedules;

return new class extends Migration
{
    public function up()
    {

        $jobs = [
            // schedule UpdateCharacter to run every minute
            UpdateCharacter::class => '* * * * *',
            // schedule UpdateCorporation to run every minute
            UpdateCorporation::class => '* * * * *',
            // schedule MaintenanceJob to run every day at 00:00
            MaintenanceJob::class => '0 0 * * *',
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
