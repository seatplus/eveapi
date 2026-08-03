<?php

namespace Seatplus\Eveapi\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Application;
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

    public CharacterInfo $testCharacter;

    #[\Override]
    protected function setUp(): void
    {
        // Hard stop: never run the suite against the dev database. The phpunit
        // <env DB_DATABASE="laravel" force="true"> override has been observed to be bypassed
        // in the local dev container (the shell exports DB_DATABASE=seatplus), in which case
        // LazilyRefreshDatabase would migrate:fresh the dev DB and wipe it. Abort loudly
        // before parent::setUp() boots anything or a factory touches the connection.
        if (env('DB_DATABASE') === 'seatplus') {
            throw new \RuntimeException('Test suite resolved DB_DATABASE=seatplus (the dev database) — aborting to avoid wiping dev data. The phpunit force override did not hold.');
        }

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
     * @param  Application  $app
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

    #[\Override]
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
