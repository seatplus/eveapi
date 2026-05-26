<?php

use Illuminate\Database\Migrations\Migration;
use Seatplus\Eveapi\Jobs\Seatplus\SdeImportJob;
use Seatplus\Eveapi\Models\Schedules;

return new class extends Migration
{
    public function up(): void
    {
        Schedules::query()->firstOrCreate(
            ['job' => SdeImportJob::class],
            ['expression' => '0 0 * * 0'],
        );

        SdeImportJob::dispatch();
    }
};
