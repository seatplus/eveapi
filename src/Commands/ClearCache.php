<?php


namespace Seatplus\Eveapi\Commands;


use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis as RedisHelper;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seatplus:cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear caches used by SeAT plus.';

    public function handle()
    {
        $this->line('SeAT plus Cache Clearing Tool');
        $this->line('');

        if (! $this->confirm('Are you sure you want to clear ALL caches (file/redis)?', true)) {

            $this->warn('Exiting without clearing cache');

            return;
        }

        $this->flushRedis();
        $this->removeFileCache();
        $this->clearArtisanCache();

    }

    private function flushRedis()
    {
        try {
            RedisHelper::flushall();
        } catch (Exception $exception) {
            $this->error('Failed to clear the Redis Cache. Error: ' . $exception->getMessage());
        }
    }

    private function removeFileCache()
    {
        // Eseye Cache Clearing
        $eseye_cache = config('eveapi.config.eseye_cache');


        if (File::isWritable($eseye_cache)) {
            $this->info('Clearing the Eseye Cache at: ' . $eseye_cache);

            if (! File::deleteDirectory($eseye_cache, true))
                $this->error('Failed to clear the Eseye Cache directory. Check permissions.');
        } else {
            $this->warn('Eseye Cache directory at ' . $eseye_cache . ' is not writable');
        }
    }

    private function clearArtisanCache()
    {
        Artisan::call('cache:clear');
    }

}
