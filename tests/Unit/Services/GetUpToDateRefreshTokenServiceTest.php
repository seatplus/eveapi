<?php

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
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
