<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\StructureRefreshTokenFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\StructureResolver;

beforeEach(function () {
    Queue::fake();
    Event::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-universe.read_structures.v1']);
    $refresh_token->save();
});

describe('isStation or recently updated structure', function () {

    beforeEach(function () {
        RefreshToken::truncate();
    });

    it('ends early if location is station', function () {

        // Arrange
        $station = Station::factory()->create();

        $location = Location::firstOrNew([
            'location_id' => $station->station_id,
            'locatable_id' => $station->station_id,
            'locatable_type' => Station::class,
        ]);

        $resolveStructurePipe = new StructureResolver;

        Log::shouldReceive()
            ->never();

        // Act
        $result = $resolveStructurePipe->handle($location);

        // Assert
        expect($result)->toBeFalse();
    });

    it('ends early if structure has recently been updated', function () {

        // Arrange
        $structure = Structure::factory()->make([
            'updated_at' => carbon()->subDays(6),
        ]);

        $structure->save();

        $structure = Structure::first();

        $location = Location::factory()->create([
            'location_id' => $structure->structure_id,
            'locatable_id' => $structure->structure_id,
            'locatable_type' => Structure::class,
        ]);

        $resolveStructurePipe = new StructureResolver;

        Log::shouldReceive()
            ->never();

        // Act
        $result = $resolveStructurePipe->handle($location);

        // Assert
        expect($result)->toBeFalse();
    });

});

describe('is potential structure', function () {

    beforeEach(function () {
        Location::truncate();
        Structure::truncate();
    });

    it('checks structure if location has not recently been updated', function () {

        // Arrange
        $location_id = 832_949_394;

        Structure::factory()->make([
            'updated_at' => carbon()->subDays(8),
            'structure_id' => $location_id,
        ])->save();

        $location = Location::firstOrNew([
            'location_id' => $location_id,
            'locatable_id' => $location_id,
            'locatable_type' => Structure::class,
        ]);

        $resolveStructurePipe = new StructureResolver($this->test_character->refresh_token);

        Log::shouldReceive('info')->once();

        // Act
        $result = $resolveStructurePipe->handle($location);

        // Assert
        expect($result)->toBeTrue();
    });

    it('creates location and returns early without refresh token', function () {

        // Arrange
        $location_id = 832_949_394;

        // Delete all RefreshTokens
        RefreshToken::truncate();

        expect(Location::count())->toBe(0);

        $location = Location::firstOrNew([
            'location_id' => $location_id,
        ]);

        $mocked_refresh_token_finder = mock(StructureRefreshTokenFinder::class)
            ->shouldReceive('findValidToken')
            ->once()
            ->andReturnNull()
            ->getMock();

        $resolveStructurePipe = new StructureResolver($mocked_refresh_token_finder);

        Log::shouldReceive('warning')
            ->once();

        // Act
        $result = $resolveStructurePipe->handle($location);

        // Assert
        expect($result)->toBeFalse();

    });

    it('logs successfully resolved structure', function () {

        // Arrange
        $location_id = 832_949_394;

        expect(Location::count())->toBe(0)
            ->and(Structure::count())->toBe(0);

        $location = Location::firstOrNew([
            'location_id' => $location_id,
        ]);

        $resolveStructurePipe = new StructureResolver($this->test_character->refresh_token);

        Log::shouldReceive('info')->once();

        // Act
        $result = $resolveStructurePipe->handle($location);

        // Assert
        expect($result)->toBeTrue()
            ->and(Location::count())->toBe(1);

    });

    it('throws error if resolving structure fails', function () {

        // Arrange
        $location_id = 832_949_394;

        expect(Location::count())->toBe(0)
            ->and(Structure::count())->toBe(0);

        $location = Location::firstOrNew([
            'location_id' => $location_id,
        ]);

        $mocked_refresh_token_finder = mock(StructureRefreshTokenFinder::class)->makePartial();

        $mocked_refresh_token_finder
            ->shouldReceive('findValidToken')
            ->once()
            ->andReturn($this->test_character->refresh_token);

        $mocked_refresh_token_finder
            ->shouldReceive('markAsResolved')
            ->once()
            ->andThrow(new \Exception('test'));

        $mocked_refresh_token_finder
            ->shouldReceive('markAsFailed')
            ->once();

        $resolveStructurePipe = new StructureResolver($mocked_refresh_token_finder);

        Log::shouldReceive('error')->once(); // once for the error and once for the exception

        // Act
        $result = $resolveStructurePipe->handle($location);

        // Assert
        expect($result)->toBeTrue()
            ->and(Location::count())->toBe(1);
    })->throws(\Exception::class);

});
