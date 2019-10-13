<?php


namespace Seatplus\Eveapi;


use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use Seatplus\Eveapi\Helpers\EseyeSetup;

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
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/eveapi.config.php', 'eveapi.config');

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

        // Configure the workers for SeAT.
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

}
