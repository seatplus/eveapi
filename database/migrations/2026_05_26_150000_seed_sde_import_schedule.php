<?php

use Illuminate\Database\Migrations\Migration;
use Seatplus\Eveapi\Jobs\Seatplus\SdeImportJob;
use Seatplus\Eveapi\Models\Schedules;

return new class extends Migration
{
    public function up(): void
    {
        // Only register the weekly recurring refresh. We deliberately do NOT import the SDE
        // here: a migration must not download tens of thousands of rows as a side effect, and
        // dispatching a queued job at migrate time makes a fresh install depend on Horizon
        // running (and blocks/breaks CI's migrate:fresh). Initial population is an explicit
        // step: `php artisan seatplus:sde-import` (add `--source=<sde.zip>` to skip the
        // download). The scheduled job then keeps it up to date.
        Schedules::query()->firstOrCreate(
            ['job' => SdeImportJob::class],
            ['expression' => '0 0 * * 0'],
        );
    }
};
