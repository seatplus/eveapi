<?php


namespace Seatplus\Eveapi\Tests;

use Illuminate\Support\Facades\Event;
use Laravel\Horizon\HorizonServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Seatplus\Eveapi\EveapiServiceProvider;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Staudenmeir\LaravelCte\DatabaseServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    public CharacterInfo $test_character;

    protected function setUp(): void
    {
        parent::setUp();


        // Fake Queue by default so nothing gets queued during tests
        //Queue::fake();

        // setup database
        $this->setupDatabase($this->app);

        Event::fakeFor(function () {
            $this->test_character = CharacterInfo::factory()->create();
        });
    }

    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    /*protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Http\Kernel', Kernel::class);
    }*/

    /**
     * Get application providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            EveapiServiceProvider::class,
            HorizonServiceProvider::class,
            DatabaseServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application  $app
     */
    private function setupDatabase($app)
    {
        // Path to our migrations to load
        //$this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        //$this->artisan('migrate', ['--database' => 'testbench']);
        $this->artisan('migrate');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        config(['database.default' => 'mysql']);

        config(['app.debug' => true]);
        //config(['activitylog.table_name' => 'activity_log']);

        //$app['router']->aliasMiddleware('auth', Authenticate::class);
    }
}
