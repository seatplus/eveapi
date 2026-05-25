<?php

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Exceptions\InvalidRefreshTokenException;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Esi\GetUpToDateRefreshTokenService;
use Seatplus\Eveapi\Services\Esi\UpdateRefreshTokenService;

beforeEach(function () {
    Event::fake();
});

it('retrieves up to date refresh token successfully', function () {

    $refreshToken = RefreshToken::factory()->create([
        'character_id' => 12345,
        'expires_on' => now()->addMinutes(5),
    ]);

    $result = (new GetUpToDateRefreshTokenService)->get($refreshToken);

    expect($result)->toBe($refreshToken);
});

it('updates refresh token if expiry is near', function () {

    $refreshToken = RefreshToken::factory()->create([
        'character_id' => 12345,
        'expires_on' => now()->addSeconds(30),
    ]);

    $updateRefreshTokenService = mock(UpdateRefreshTokenService::class, function (MockInterface $mock) use ($refreshToken) {

        $new_token = RefreshToken::factory()->make([
            'character_id' => $refreshToken->character_id,
            'expires_on' => now()->addMinutes(5),
        ]);

        $mock->shouldReceive('update')
            ->with($refreshToken)
            ->andReturn($new_token);
    });

    $service = new GetUpToDateRefreshTokenService($updateRefreshTokenService);

    $result = $service->get($refreshToken);

    expect($result->expires_on)->toBeGreaterThan(now()->addSeconds(30));
});

it('throws request failed exception', function () {

    $refreshToken = mock(RefreshToken::class, function (MockInterface $mock) {

        $mock->makePartial()
            ->shouldReceive('refresh')
            ->andThrow(new RequestFailedException(new Exception('failed'), new EsiResponse(json_encode([]), [], 'now', 200)));
    });

    $service = new GetUpToDateRefreshTokenService;

    expect(fn () => $service->get($refreshToken))->toThrow(RequestFailedException::class);
});

it('throws InvalidRefreshTokenException when OAuth returns 400', function () {

    $refreshToken = RefreshToken::factory()->create([
        'character_id' => 12345,
        'expires_on' => now()->addSeconds(30),
    ]);

    $updateRefreshTokenService = mock(UpdateRefreshTokenService::class, function (MockInterface $mock) {
        $mock->shouldReceive('update')
            ->once()
            ->andThrow(new RequestFailedException(
                new Exception('invalid_grant', 400),
                new EsiResponse(json_encode(['error' => 'invalid_grant']), [], 'now', 400),
            ));
    });

    $service = new GetUpToDateRefreshTokenService($updateRefreshTokenService);

    expect(fn () => $service->get($refreshToken))->toThrow(InvalidRefreshTokenException::class);
});

it('calls update only once when two calls race for the same token', function () {

    $refreshToken = RefreshToken::factory()->create([
        'character_id' => 12345,
        'expires_on' => now()->addSeconds(30),
    ]);

    $callCount = 0;

    $updateRefreshTokenService = mock(UpdateRefreshTokenService::class, function (MockInterface $mock) use ($refreshToken, &$callCount) {
        $mock->shouldReceive('update')
            ->once()
            ->andReturnUsing(function () use ($refreshToken, &$callCount) {
                $callCount++;

                return RefreshToken::updateOrCreate(
                    ['character_id' => $refreshToken->character_id],
                    ['expires_on' => now()->addMinutes(20)],
                );
            });
    });

    $service = new GetUpToDateRefreshTokenService($updateRefreshTokenService);

    // First call acquires lock, refreshes token
    $result1 = $service->get($refreshToken);
    // Second call re-reads the now-fresh token from DB, skips update
    $result2 = $service->get($refreshToken);

    expect($callCount)->toBe(1)
        ->and($result1->character_id)->toBe(12345)
        ->and($result2->character_id)->toBe(12345);
});
