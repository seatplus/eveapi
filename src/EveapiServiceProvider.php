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
use Seatplus\Eveapi\Commands\ClearCache;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseConstellationCreated;
use Seatplus\Eveapi\Events\UniverseSystemCreated;
use Seatplus\Eveapi\Events\UpdatingRefreshTokenEvent;
use Seatplus\Eveapi\Listeners\DispatchGetConstellationById;
use Seatplus\Eveapi\Listeners\DispatchGetRegionById;
use Seatplus\Eveapi\Listeners\DispatchGetSystemJobSubscriber;
use Seatplus\Eveapi\Listeners\ReactOnFreshRefreshToken;
use Seatplus\Eveapi\Listeners\UpdatingRefreshTokenListener;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Schedules;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Observers\BalanceObserver;
use Seatplus\Eveapi\Observers\CharacterAffiliationObserver;
use Seatplus\Eveapi\Observers\CharacterAssetObserver;
use Seatplus\Eveapi\Observers\CharacterInfoObserver;
use Seatplus\Eveapi\Observers\ContactObserver;
use Seatplus\Eveapi\Observers\ContractItemObserver;
use Seatplus\Eveapi\Observers\ContractObserver;
use Seatplus\Eveapi\Observers\CorporationMemberTrackingObserver;
use Seatplus\Eveapi\Observers\GroupObserver;
use Seatplus\Eveapi\Observers\SkillObserver;
use Seatplus\Eveapi\Observers\SkillQueueObserver;
use Seatplus\Eveapi\Observers\TypeObserver;
use Seatplus\Eveapi\Observers\WalletTransactionObserver;
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
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations/');

        // Configure the queue dashboard
        $this->configureHorizon();

        // Add Horizon Snapshot schedule
        $this->addHorizonSnapshotSchedule();

        // Add other schedules
        $this->addSchedules();

        // Add event listeners
        $this->addEventListeners();

        // Add commands
        $this->addCommands();

        RateLimiter::for(
            'corporation_batch',
            fn ($job) => Limit::perHour(1)
            ->by($job->corporation_id ?? 'corporation_batch')
        );

        RateLimiter::for(
            'character_batch',
            fn ($job) => Limit::perHour(1)
            ->by($job->character_id ?? 'character_batch')
        );
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.config.php', 'eveapi.config');

        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.scopes.php', 'eveapi.scopes');

        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.permissions.php', 'eveapi.permissions');

        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.updateJobs.php', 'seatplus.updateJobs');

        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.jobs.php', 'eveapi.jobs');

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
                ],
            ],
            'production' => [
                'seatplus-workers' => [
                    'connection' => 'redis',
                    'queue' => ['high', 'default'],
                    'balance' => 'auto',
                    'minProcesses' => 1,
                    'maxProcesses' => (int) env(self::QUEUE_BALANCING_WORKERS, 4),
                    'tries' => 1,
                    'timeout' => 900, // 15 minutes
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
        CharacterAffiliation::observe(CharacterAffiliationObserver::class);
        Asset::observe(CharacterAssetObserver::class);

        //Corporation Observers
        CorporationMemberTracking::observe(CorporationMemberTrackingObserver::class);

        //Contact Observers
        Contact::observe(ContactObserver::class);

        //Contract Observer
        Contract::observe(ContractObserver::class);
        ContractItem::observe(ContractItemObserver::class);

        //WalletObserver
        WalletTransaction::observe(WalletTransactionObserver::class);
        Balance::observe(BalanceObserver::class);

        //SkillObserver
        Skill::observe(SkillObserver::class);
        SkillQueue::observe(SkillQueueObserver::class);
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

            // Cleanup Batches Table
            $schedule->command('queue:prune-batches')->daily();
        });
    }

    private function addCommands(): void
    {
        $this->commands([
            ClearCache::class,
        ]);
    }
}
