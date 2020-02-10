<?php


namespace Seatplus\Eveapi\Tests;

use Clockwork\Support\Laravel\ClockworkServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Horizon\HorizonServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Seatplus\Eveapi\EveapiServiceProvider;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

abstract class TestCase extends OrchestraTestCase
{
    protected $test_user;

    protected $test_character;

    protected function setUp(): void
    {

        parent::setUp();

        // setup database
        $this->setupDatabase($this->app);

        // setup factories
        $this->withFactories(__DIR__ . '/database/factories');

        Event::fakeFor(function () {
            $this->test_character = factory(CharacterInfo::class)->create();
        });

        //dump(class_basename($this));
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
            ClockworkServiceProvider::class
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application  $app
     */
    private function setupDatabase($app)
    {
        // Path to our migrations to load
        //$this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->artisan('migrate', ['--database' => 'testbench']);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Use memory SQLite, cleans it self up
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        config(['app.debug' => true]);

        //$app['router']->aliasMiddleware('auth', Authenticate::class);
    }

}
