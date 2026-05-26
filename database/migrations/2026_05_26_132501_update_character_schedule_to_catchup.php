<?php

use Illuminate\Database\Migrations\Migration;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Models\Schedules;

return new class extends Migration
{
    public function up(): void
    {
        // Change UpdateCharacter from every-minute polling to every-30-minute catchup
        Schedules::query()
            ->where('job', UpdateCharacter::class)
            ->update(['expression' => '*/30 * * * *']);
    }
};
