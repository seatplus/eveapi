<?php

namespace Seatplus\Eveapi\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Horizon\HorizonServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Seatplus\Eveapi\EveapiServiceProvider;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

abstract class TestCase extends OrchestraTestCase
{
    use LazilyRefreshDatabase;

    public CharacterInfo $test_character;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Setup factories
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Seatplus\\Eveapi\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Event::fakeFor(function () {
            $this->test_character = CharacterInfo::factory()->create();
        });
    }

    /**
     * Get application providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    #[\Override]
    protected function getPackageProviders($app)
    {
        return [
            EveapiServiceProvider::class,
            HorizonServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    #[\Override]
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'mysql');

        // setup batching database
        app('config')->set('queue.batching.database', 'mysql');
    }

    #[\Override]
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
