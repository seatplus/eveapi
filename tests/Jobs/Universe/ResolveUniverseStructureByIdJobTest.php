<?php

use Illuminate\Support\Facades\Event;
use Seatplus\EsiClient\EsiClient;
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
});

it('creates structure', function () {
    $mockData = Structure::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mockData->only(['name', 'owner_id', 'solar_system_id', 'type_id'])));

    $job = new ResolveUniverseStructureByIdJob(12345, $mockData->structure_id);
    $job->executeJob($esi);

    expect(Structure::where('structure_id', $mockData->structure_id)->exists())->toBeTrue();
});

it('creates location', function () {
    $mockData = Structure::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mockData->only(['name', 'owner_id', 'solar_system_id', 'type_id'])));

    $job = new ResolveUniverseStructureByIdJob(12345, $mockData->structure_id);
    $job->executeJob($esi);

    expect(Location::where('location_id', $mockData->structure_id)->exists())->toBeTrue();
});

it('creates polymorphic relationship', function () {
    $mockData = Structure::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mockData->only(['name', 'owner_id', 'solar_system_id', 'type_id'])));

    $job = new ResolveUniverseStructureByIdJob(12345, $mockData->structure_id);
    $job->executeJob($esi);

    $location = Location::find($mockData->structure_id);

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
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new ResolveUniverseStructureByIdJob(12345, 99999999);
    $job->executeJob($esi);

    expect(Structure::count())->toBe(0)
        ->and(Location::count())->toBe(0);
});

it('returns the refresh token for the character', function () {
    $token = RefreshToken::factory()->create();

    $job = new ResolveUniverseStructureByIdJob($token->character_id, 99999999);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class)
        ->and($job->getRefreshToken()->character_id)->toBe($token->character_id);
});
