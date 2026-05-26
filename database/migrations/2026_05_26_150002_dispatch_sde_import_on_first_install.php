<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Seatplus\Eveapi\Jobs\Seatplus\SdeImportJob;

return new class extends Migration
{
    public function up(): void
    {
        // Only dispatch on a fresh installation — when universe_categories is empty.
        // Re-running migrations on an existing install is safe: the job is not re-queued.
        if (DB::table('universe_categories')->count() === 0) {
            SdeImportJob::dispatch();
        }
    }
};
