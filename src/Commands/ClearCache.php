<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

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
    protected $signature = 'seatplus:cache:clear {--force}';

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

        if(! $this->option('force'))
            if (! $this->confirm('Are you sure you want to clear ALL caches (file/redis)?', true)) {

                $this->warn('Exiting without clearing cache');

                return;
            }

        $this->flushRedis();

        $this->removeFileCache();

        $this->clearArtisanCache();

        $this->info('success');
    }

    private function flushRedis()
    {
        $this->info('Clearing the Redis Cache');

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

        if(! File::isWritable($eseye_cache)) {
            $this->error('Eseye Cache directory at ' . $eseye_cache . ' is not writable');

            return;
        }

        $this->info('Clearing the Eseye Cache at: ' . $eseye_cache);

        if (! File::deleteDirectory($eseye_cache, true))
            $this->error('Failed to clear the Eseye Cache directory. Check permissions.');
    }

    private function clearArtisanCache()
    {
        $this->info('Clearing the Artisan Cache');
        Artisan::call('cache:clear');
    }
}
