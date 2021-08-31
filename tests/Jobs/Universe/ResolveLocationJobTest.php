<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStationByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStructureByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Queue::fake();

    Event::fake([
        UniverseStationCreated::class,
        UniverseStructureCreated::class,
        RefreshTokenCreated::class,
    ]);
});

it('checks only asset safety checker', function () {
    Asset::factory()->create([
        'location_id' => 2004,
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
        'location_type' => 'other',
    ]);

    buildJob(2004)->handle();

    $this->assertNull(Location::find(2004));
});

/**
 * @runTestsInSeparateProcesses
 */
it('checks only station checker', function () {
    $test_assets = Asset::factory()->create([
        'location_id' => 60003760,
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
        'location_type' => 'station',
    ]);

    $mock_data = Station::factory()->make([
        'station_id' => 60003760,
    ]);

    Queue::fake();
    Queue::assertNothingPushed();

    buildJob(60003760)->handle();

    Queue::assertPushedOn('high', fn (ResolveUniverseStationByIdJob $job) => $job->location_id === $mock_data->station_id);
});

/**
 * @runTestsInSeparateProcesses
 */
it('checks only station older then a week', function () {
    $location_id = 60003760;

    Asset::factory()->count(5)->create([
        'location_id' => $location_id,
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
        'location_type' => 'station',
    ]);

    $mock_data = Station::factory()->create([
        'station_id' => $location_id,
        'updated_at' => carbon()->subWeeks(2),
    ]);

    Location::factory()->create([
        'location_id' => $location_id,
        'locatable_id' => $location_id,
        'locatable_type' => Station::class,
    ]);

    $this->assertTrue(carbon(Station::find($location_id)->updated_at)->isBefore(carbon()->subWeek()));

    Queue::fake();
    Queue::assertNothingPushed();

    buildJob($location_id)->handle();

    Queue::assertPushedOn('high', fn (ResolveUniverseStationByIdJob $job) => $job->location_id === $location_id);
});

it('checks no station younger then a week', function () {
    $location_id = 60003760;

    Asset::factory()->count(5)->create([
        'location_id' => $location_id,
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
        'location_type' => 'station',
    ]);

    Station::factory()->create([
        'station_id' => $location_id,
        'updated_at' => carbon()->subDays(2),
    ]);

    Location::factory()->create([
        'location_id' => $location_id,
        'locatable_id' => $location_id,
        'locatable_type' => Station::class,
    ]);

    buildJob($location_id)->handle();

    $this->assertNotNull(Location::find($location_id)->locatable);
    $this->assertTrue(carbon(Station::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));
    $this->assertTrue(carbon(Station::find($location_id)->updated_at)->isBefore(carbon()->subDay()));
});

/**
 * @runTestsInSeparateProcesses
 */
it('checks only structure checker', function () {
    $test_assets = Asset::factory()->count(5)->create([
        'location_id' => 1028832949394,
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
        'location_type' => 'station',
    ]);

    $mock_data = Structure::factory()->make([
        'station_id' => 1028832949394,
    ]);

    Queue::fake();
    Queue::assertNothingPushed();

    buildJob(1028832949394)->handle();

    Queue::assertPushedOn('low', fn (ResolveUniverseStructureByIdJob $job) => $job->location_id === $mock_data->station_id);
});

/**
 * @runTestsInSeparateProcesses
 */
it('checks only structure older then a week', function () {
    $location_id = 1_028_832_949_394;

    Asset::factory()->count(5)->create([
        'location_id' => $location_id,
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
        'location_type' => 'station',
    ]);

    $mock_data = Structure::factory()->create([
        'structure_id' => $location_id,
        'updated_at' => carbon()->subWeeks(2),
    ]);

    Location::factory()->create([
        'location_id' => $location_id,
        'locatable_id' => $location_id,
        'locatable_type' => Structure::class,
    ]);

    $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isBefore(carbon()->subWeek()));

    Queue::fake();
    Queue::assertNothingPushed();

    buildJob($location_id)->handle();

    Queue::assertPushedOn('low', fn (ResolveUniverseStructureByIdJob $job) => $job->location_id === $location_id);

    /*$this->assertNotNull(Location::find($location_id)->locatable);

    $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));*/
});

it('checks no structure younger then a week', function () {
    $location_id = 1028832949394;

    Asset::factory()->count(5)->create([
        'location_id' => $location_id,
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
        'location_type' => 'other',
    ]);

    Structure::factory()->create([
        'structure_id' => $location_id,
        'updated_at' => carbon()->subDays(2),
    ]);

    Location::factory()->create([
        'location_id' => $location_id,
        'locatable_id' => $location_id,
        'locatable_type' => Structure::class,
    ]);

    $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));

    Queue::fake();
    Queue::assertNothingPushed();

    buildJob($location_id)->handle();

    Queue::assertNothingPushed();

    /*$this->assertNotNull(Location::find($location_id)->locatable);

    $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));
    $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isBefore(carbon()->subDay()));*/
});

// Helpers
function buildJob(int $location_id) : ResolveLocationJob
{
    $refresh_token = $this->test_character->refresh_token;
    $refresh_token->scopes = ['esi-universe.read_structures.v1'];
    Event::fakeFor(fn () => $refresh_token->save());

    return new ResolveLocationJob($location_id, $refresh_token);
}
