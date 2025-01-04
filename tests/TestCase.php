<?php

namespace Seatplus\Eveapi\Tests;

use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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

        Queue::fake();

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
    protected function defineEnvironment($app)
    {

        tap($app['config'], function (Repository $config) {
            $config->set('database.connections.mysql.port', '3306');
            $config->set('database.connections.mysql.password', 'secret');
        });

    }

    #[\Override]
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
