<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
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

namespace Seatplus\Eveapi;

use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use Seatplus\Eveapi\Commands\CheckJobsCommand;
use Seatplus\Eveapi\Commands\ClearCache;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseConstellationCreated;
use Seatplus\Eveapi\Events\UniverseSystemCreated;
use Seatplus\Eveapi\Events\UpdatingRefreshTokenEvent;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Listeners\DispatchGetConstellationById;
use Seatplus\Eveapi\Listeners\DispatchGetRegionById;
use Seatplus\Eveapi\Listeners\DispatchGetSystemJobSubscriber;
use Seatplus\Eveapi\Listeners\ReactOnFreshRefreshToken;
use Seatplus\Eveapi\Listeners\UpdatingRefreshTokenListener;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Schedules;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Observers\CharacterInfoObserver;
use Seatplus\Eveapi\Observers\GroupObserver;
use Seatplus\Eveapi\Observers\TypeObserver;
use Seatplus\Eveapi\Services\Esi\EsiClientSetup;

class EveapiServiceProvider extends ServiceProvider
{
    /**
     * The environment variable name used to setup the queue daemon balancing mode.
     */
    const QUEUE_BALANCING_MODE = 'QUEUE_BALANCING_MODE';

    /**
     * The environment variable name used to setup the queue workers amount.
     */
    const QUEUE_BALANCING_WORKERS = 'QUEUE_WORKERS';

    public function boot()
    {
        //Add Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');

        // Configure the queue dashboard
        $this->configureHorizon();

        // Add Horizon Snapshot schedule
        $this->addHorizonSnapshotSchedule();

        // Add Horizon terminate schedule
        $this->addHorizonTerminateSchedule();

        // Add other schedules
        $this->addSchedules();

        // Add event listeners
        $this->addEventListeners();

        // Add commands
        $this->addCommands();

        // Add Rate Limiters
        $this->addRateLimiters();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eveapi.config.php', 'eveapi.config');

        $this->mergeConfigFrom(__DIR__.'/../config/eveapi.scopes.php', 'eveapi.scopes');

        $this->mergeConfigFrom(__DIR__.'/../config/eveapi.permissions.php', 'eveapi.permissions');

        $this->mergeConfigFrom(__DIR__.'/../config/eveapi.updateJobs.php', 'seatplus.updateJobs');

        $this->mergeConfigFrom(__DIR__.'/../config/eveapi.jobs.php', 'eveapi.jobs');

        // Eseye Singleton
        $this->app->singleton('esi-client', function () {
            return new EsiClientSetup;
        });
    }

    /**
     * Configure Horizon.
     *
     * This includes the access rules for the dashboard, as
     * well as the number of workers to use for the job processor.
     */
    public function configureHorizon()
    {
        // Require the queue_manager role to view the dashboard
        Horizon::auth(function ($request) {
            if (is_null($request->user())) {
                return false;
            }

            return $request->user()->can('queue_manager');
        });

        // attempt to parse the QUEUE_BALANCING variable into a boolean
        $balancing_mode = filter_var(env(self::QUEUE_BALANCING_MODE, false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        // in case the variable cannot be parsed into a boolean, assign the environment value itself
        if (is_null($balancing_mode)) {
            $balancing_mode = env(self::QUEUE_BALANCING_MODE, false);
        }

        // Configure the workers for SeAT plus.
        $horizon_environments = [
            'local' => [
                'seatplus-workers' => [
                    'connection' => 'redis',
                    'queue' => ['high', 'medium', 'low', 'default'],
                    'balance' => $balancing_mode,
                    'processes' => (int) env(self::QUEUE_BALANCING_WORKERS, 4),
                    'block_for' => 5,
                    'timeout' => 120, // 2 minutes
                    'nice' => 10, //Allowed values are between 0 and 19
                    'maxTime' => 3600,
                    'maxJobs' => 1000,
                ],
            ],
            'production' => [
                'seatplus-workers' => [
                    'connection' => 'redis',
                    'queue' => ['high', 'medium', 'low', 'default'],
                    'balance' => 'auto',
                    'minProcesses' => 1,
                    'maxProcesses' => (int) env(self::QUEUE_BALANCING_WORKERS, 4),
                    'tries' => 1,
                    'nice' => 10, //Allowed values are between 0 and 19
                    'timeout' => 900, // 15 minutes
                    'maxTime' => 3600,
                    'maxJobs' => 1000,
                ],
            ],
        ];

        // Set the environment configuration.
        config(['horizon.environments' => $horizon_environments]);

        // remove the default config worker
        config(['horizon.defaults' => []]);
    }

    private function addHorizonSnapshotSchedule()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('horizon:snapshot')->everyFiveMinutes();
        });
    }

    private function addHorizonTerminateSchedule()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('horizon:terminate')->hourly();
        });
    }

    private function addEventListeners()
    {
        $this->app->events->subscribe(DispatchGetSystemJobSubscriber::class);
        $this->app->events->listen(UniverseSystemCreated::class, DispatchGetConstellationById::class);
        $this->app->events->listen(UniverseConstellationCreated::class, DispatchGetRegionById::class);
        $this->app->events->listen(RefreshTokenCreated::class, ReactOnFreshRefreshToken::class);
        $this->app->events->listen(UpdatingRefreshTokenEvent::class, UpdatingRefreshTokenListener::class);

        Type::observe(TypeObserver::class);
        Group::observe(GroupObserver::class);

        //Character Observers
        CharacterInfo::observe(CharacterInfoObserver::class);
    }

    private function addSchedules()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Check that the schedules table exists. This
            // could cause a fatal error if the app is
            // still being setup or the db has not yet
            // been configured. This is a relatively ugly
            // hack as this schedule() method is core to
            // the framework.
            try {
                DB::connection();
                if (! Schema::hasTable('schedules')) {
                    throw new Exception('Schema schedules does not exist');
                }
            } catch (Exception) {
                return;
            }

            Schedules::cursor()->each(function ($entry) use ($schedule) {
                $schedule->job(new $entry->job)->cron($entry->expression);
            });

            // Run Character Affiliation Job every five minutes to updated outdated affiliations.
            $schedule->job(new CharacterAffiliationJob)->everyFiveMinutes();

            // Cleanup Batches Table
            $schedule->command('queue:prune-batches')->daily();
        });
    }

    private function addCommands(): void
    {
        $this->commands([
            ClearCache::class,
            CheckJobsCommand::class,
        ]);
    }

    private function addRateLimiters()
    {
        RateLimiter::for(
            'corporation_batch',
            fn ($job) => Limit::perHour(1)
                ->by($job->corporation_id ?? 'corporation_batch')
        );

        RateLimiter::for(
            'character_batch',
            function ($job) {
                $character_id = $job?->refresh_token?->character_id;
                $queue_name = $job?->queue;

                // if queue is high then we need no rate limiting
                if ($queue_name === 'high') {
                    return Limit::none();
                }

                return Limit::perHour(1)->by("character_batch_{$character_id}_{$queue_name}");
            }
        );
    }
}
