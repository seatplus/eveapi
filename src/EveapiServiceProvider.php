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

namespace Seatplus\Eveapi;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseConstellationCreated;
use Seatplus\Eveapi\Events\UniverseSystemCreated;
use Seatplus\Eveapi\Helpers\EseyeSetup;
use Seatplus\Eveapi\Listeners\DispatchGetConstellationById;
use Seatplus\Eveapi\Listeners\DispatchGetRegionById;
use Seatplus\Eveapi\Listeners\DispatchGetSystemJobSubscriber;
use Seatplus\Eveapi\Listeners\ReactOnFreshRefreshToken;

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
        $this->configure_horizon();

        // Add Horizon Snapshot schedule
        $this->addHorizonSnapshotSchedule();

        // Add routes
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');

        // Add event listeners
        $this->addEventListeners();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.config.php', 'eveapi.config');

        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.scopes.php', 'eveapi.scopes');

        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.permissions.php', 'eveapi.permissions');

        // Eseye Singleton
        $this->app->singleton('esi-client', function () {

            return new EseyeSetup;
        });
    }

    /**
     * Configure Horizon.
     *
     * This includes the access rules for the dashboard, as
     * well as the number of workers to use for the job processor.
     */
    public function configure_horizon()
    {
        // Require the queue_manager role to view the dashboard
        Horizon::auth(function ($request) {
            if (is_null($request->user()))
                return false;
            //return $request->user()->has('queue_manager', false);
            return true;
        });

        // attempt to parse the QUEUE_BALANCING variable into a boolean
        $balancing_mode = filter_var(env(self::QUEUE_BALANCING_MODE, false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        // in case the variable cannot be parsed into a boolean, assign the environment value itself
        if (is_null($balancing_mode))
            $balancing_mode = env(self::QUEUE_BALANCING_MODE, false);

        // Configure the workers for SeAT plus.
        $horizon_environments = [
            'local' => [
                'seatplus-workers' => [
                    'connection' => 'redis',
                    'queue'      => ['high', 'medium', 'low', 'default'],
                    'balance'    => $balancing_mode,
                    'processes'  => (int) env(self::QUEUE_BALANCING_WORKERS, 4),
                    'tries'      => 1,
                    'timeout'    => 900, // 15 minutes
                ],
            ],
        ];

        // Set the environment configuration.
        config(['horizon.environments' => $horizon_environments]);
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
    }
}
