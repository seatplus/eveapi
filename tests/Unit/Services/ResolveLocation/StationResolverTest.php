<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\StationResolver;

beforeEach(function () {
    Queue::fake();
    Event::fake();
});

describe('isStructure or recently updated station', function () {

    it('ends early if location is structure', function () {

        // Arrange
        $structure = Structure::factory()->make();

        $structure->save();

        $structure = Structure::first();

        $location = Location::factory()->create([
            'location_id' => $structure->structure_id,
            'locatable_id' => $structure->structure_id,
            'locatable_type' => Structure::class,
        ]);

        $resolveStationPipe = new StationResolver;

        Log::shouldReceive()
            ->never();

        // Act
        $result = $resolveStationPipe->handle($location);

        // Assert
        expect($result)->toBeFalse();
    });

    it('ends early if station has recently been updated', function () {

        // Arrange
        $station = Station::factory()->create([
            'updated_at' => carbon()->subDays(6),
        ]);

        $location = Location::factory()->create([
            'location_id' => $station->station_id,
            'locatable_id' => $station->station_id,
            'locatable_type' => Station::class,
        ]);

        $resolveStationPipe = new StationResolver;

        Log::shouldReceive()
            ->never();

        // Act
        $result = $resolveStationPipe->handle($location);

        // Assert
        expect($result)->toBeFalse();
    });

});

describe('is potential station', function () {

    beforeEach(function () {
        Location::truncate();
        Station::truncate();
    });

    it('returns true if station has not recently been updated', function () {

        // Arrange
        $station = Station::factory()->create([
            'updated_at' => carbon()->subDays(8),
        ]);

        $location = Location::factory()->create([
            'location_id' => $station->station_id,
            'locatable_id' => $station->station_id,
            'locatable_type' => Station::class,
        ]);

        $resolveStationPipe = new StationResolver;

        // Act
        $result = $resolveStationPipe->handle($location);

        // Assert
        expect($result)->toBeTrue();
    });

    it('creates location', function () {

        // Arrange
        $location_id = 60_005_617;

        $location = Location::firstOrNew([
            'location_id' => $location_id,
        ]);

        Log::shouldReceive('info')
            ->once();

        // Act
        $result = (new StationResolver)->handle($location);

        // Assert
        expect(Location::count())->toBe(1)
            ->and($result)->toBeTrue();

    });

    it('logs successfully resolved station', function () {

        // Arrange
        $location_id = 60_005_617;

        $location = Location::firstOrNew([
            'location_id' => $location_id,
        ]);

        Log::shouldReceive('info')->once();

        // Act
        $stationResolver = new StationResolver;
        $result = $stationResolver->handle($location);

        // Assert
        expect($result)->toBeTrue();

    });

    it('throws error if resolving station fails', function () {

        // Arrange
        $location_id = 60_005_617;

        expect(Location::count())->toBe(0)
            ->and(Structure::count())->toBe(0);

        $location = Location::firstOrNew([
            'location_id' => $location_id,
        ]);

        $test_class = new class extends StationResolver
        {
            protected function dispatchStationResolutionJob(Location $location): void
            {
                throw new \Exception('test');
            }
        };

        Log::shouldReceive('error')->once(); // once for the error

        // Act
        $result = $test_class->handle($location);

        // Assert
        expect($result)->toBeTrue();
    })->throws(\Exception::class);

});

it('returns false if location is not a potential station', function () {
    $location = mock(Location::class, function (\Mockery\MockInterface $mock) {
        $mock->shouldReceive('getAttribute')
            ->with('location_id')
            ->andReturn(59_000_000);

        $mock->shouldReceive('getAttribute')
            ->with('locatable')
            ->andReturnNull();
    });
    // $location->location_id = 59_000_000;

    $resolver = new StationResolver;

    expect($resolver->handle($location))->toBeFalse();
});
