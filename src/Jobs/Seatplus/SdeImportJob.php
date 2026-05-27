<?php

namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class SdeImportJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function handle(): void
    {
        Artisan::call('seatplus:sde-import');
    }
}
