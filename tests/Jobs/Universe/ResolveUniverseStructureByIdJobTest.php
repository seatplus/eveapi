<?php

use Illuminate\Support\Facades\Event;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStructureByIdJob;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;

beforeEach(function () {
    Event::fake([
        UniverseStructureCreated::class,
        RefreshTokenCreated::class,
    ]);

    $this->refresh_token = RefreshToken::factory()->scopes(['esi-universe.read_structures.v1'])->create();
});

it('creates structure', function () {
    $mock_data = buildStructureMockEsiData();

    //Assert that no structure is created
    $this->assertDatabaseMissing('universe_structures', [
        'structure_id' => $mock_data->structure_id,
    ]);

    (new ResolveUniverseStructureByIdJob($this->refresh_token->character_id, $mock_data->structure_id))->handle();

    //Assert that structure is created
    $this->assertDatabaseHas('universe_structures', [
        'structure_id' => $mock_data->structure_id,
    ]);
});

it('creates location', function () {
    $mock_data = buildStructureMockEsiData();

    //Assert that no structure is created
    $this->assertDatabaseMissing('universe_locations', [
        'location_id' => $mock_data->structure_id,
    ]);

    (new ResolveUniverseStructureByIdJob($this->refresh_token->character_id, $mock_data->structure_id))->handle();

    //Assert that structure is created
    $this->assertDatabaseHas('universe_locations', [
        'location_id' => $mock_data->structure_id,
    ]);
});

it('creates polymorphic relationship', function () {
    $mock_data = buildStructureMockEsiData();

    (new ResolveUniverseStructureByIdJob($this->refresh_token->character_id, $mock_data->structure_id))->handle();

    $location = Location::find($mock_data->structure_id);

    expect($location->locatable)->toBeInstanceOf(Structure::class);
});

it('returns correct tags array for universe structure job', function () {
    $job = new ResolveUniverseStructureByIdJob(12345, 67890);

    $tags = $job->tags();

    expect($tags)->toBe([
        'resolve',
        'universe',
        'structure',
        'location_id:67890',
    ]);
});

it('does not upsert structure and location when response is cached', function () {
    $response = mock(EsiResponse::class, function ($mock) {
        $mock->shouldReceive('isCachedLoad')->andReturn(true);
    });

    $job = mock(ResolveUniverseStructureByIdJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(Structure::count())->toBe(0)
        ->and(Location::count())->toBe(0);
});

// Helpers
function buildStructureMockEsiData()
{
    $mock_data = Structure::factory()->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
