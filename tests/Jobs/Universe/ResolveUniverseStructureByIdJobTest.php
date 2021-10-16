<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStructureByIdJob;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Event::fake([
        UniverseStructureCreated::class,
        RefreshTokenCreated::class,
    ]);

    $this->refresh_token = RefreshToken::factory()->scopes(['esi-universe.read_structures.v1'])->create();
});

/**
 * @runTestsInSeparateProcesses
 */
it('creates structure', function () {
    $mock_data = buildStructureMockEsiData();

    //Assert that no structure is created
    $this->assertDatabaseMissing('universe_structures', [
        'structure_id' => $mock_data->structure_id,
    ]);

    (new ResolveUniverseStructureByIdJob($this->refresh_token, $mock_data->structure_id))->handle();

    //Assert that structure is created
    $this->assertDatabaseHas('universe_structures', [
        'structure_id' => $mock_data->structure_id,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
it('creates location', function () {
    $mock_data = buildStructureMockEsiData();

    //Assert that no structure is created
    $this->assertDatabaseMissing('universe_locations', [
        'location_id' => $mock_data->structure_id,
    ]);

    (new ResolveUniverseStructureByIdJob($this->refresh_token, $mock_data->structure_id))->handle();

    //Assert that structure is created
    $this->assertDatabaseHas('universe_locations', [
        'location_id' => $mock_data->structure_id,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
it('creates polymorphic relationship', function () {
    $mock_data = buildStructureMockEsiData();

    (new ResolveUniverseStructureByIdJob($this->refresh_token, $mock_data->structure_id))->handle();

    $location = Location::find($mock_data->structure_id);

    expect($location->locatable)->toBeInstanceOf(Structure::class);
});

// Helpers
function buildStructureMockEsiData()
{
    $mock_data = Structure::factory()->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
