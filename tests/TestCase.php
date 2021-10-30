<?php


namespace Seatplus\Eveapi\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\HorizonServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PDO;
use Seatplus\Eveapi\EveapiServiceProvider;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Spatie\LaravelRay\RayServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected CharacterInfo $test_character;

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
            RayServiceProvider::class,
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

        // Use memory SQLite, cleans it self up
        config(['database.default' => 'mysql']);
        /*$app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'mysql',
            //'url' => env('DATABASE_URL'),
            'host' => '172.0.0.1',
            'port' => '3308',
            'database' => 'testbench',
            'username' => 'default',
            'password' => 'secret',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ]);*/

        config(['app.debug' => true]);
        config(['activitylog.table_name' => 'activity_log']);

        //$app['router']->aliasMiddleware('auth', Authenticate::class);
    }
}
