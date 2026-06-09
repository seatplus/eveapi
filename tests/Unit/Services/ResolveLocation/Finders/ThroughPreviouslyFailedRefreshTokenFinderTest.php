<?php

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\LocationRefreshToken;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughPreviouslyFailedRefreshTokenFinder;

it('returns null when no valid record is found', function () {
    $finder = new ThroughPreviouslyFailedRefreshTokenFinder;
    $locationId = 1;
    $tracings = new Collection([
        createLocationRefreshToken(6),
        createLocationRefreshToken(7),
    ]);

    $result = $finder->handle($locationId, $tracings);

    expect($result)->toBeNull();
});

function createLocationRefreshToken(int $attempts)
{
    $mock = Mockery::mock(LocationRefreshToken::class);
    $mock->shouldReceive('getAttribute')
        ->with('attempts')
        ->andReturn($attempts);
    $mock->shouldReceive('getAttribute')
        ->with('updated_at')
        ->andReturn(now());

    $mock->shouldReceive('offsetExists')
        ->andReturn(true);

    $mock->shouldReceive('offsetGet')
        ->andReturn(true);

    return $mock;
}
