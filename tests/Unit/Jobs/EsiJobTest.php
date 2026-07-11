<?php

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\EsiErrorLimitedException;
use Seatplus\EsiClient\Exceptions\EsiRateLimitedException;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\EsiSchema\Resources\Mail\GetCharactersCharacterIdMail;
use Seatplus\Eveapi\Exceptions\InvalidRefreshTokenException;
use Seatplus\Eveapi\Services\Esi\GetUpToDateRefreshTokenService;
use Seatplus\Eveapi\Services\Esi\InvalidTokenThrottleService;
use Seatplus\Eveapi\Services\Esi\RecordingEsiClient;
use Seatplus\Eveapi\Tests\Unit\Jobs\Support\TestableEsiJob;
use Seatplus\Eveapi\Tests\Unit\Jobs\Support\TestableEsiJobWithOperation;
use Seatplus\Eveapi\Tests\Unit\Jobs\Support\TestableEsiJobWithoutRateLimit;

it('calls executeJob via handle', function () {
    $esi = Mockery::mock(EsiClient::class);
    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $throttle = Mockery::mock(InvalidTokenThrottleService::class);

    $job = new TestableEsiJob;
    $job->handle($esi, $tokenService, $throttle);

    expect($job->executed)->toBeTrue();
});

it('does not apply auth for public endpoints', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldNotReceive('withToken');

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $tokenService->shouldNotReceive('get');

    $throttle = Mockery::mock(InvalidTokenThrottleService::class);

    $job = new TestableEsiJob;
    $job->handle($esi, $tokenService, $throttle);
});

it('injects auth token when getRefreshToken returns a token', function () {
    $refreshToken = testCharacter()->refreshToken;

    $authenticatedEsi = Mockery::mock(EsiClient::class);

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')
        ->with($refreshToken->getRawOriginal('token'))
        ->once()
        ->andReturn($authenticatedEsi);

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $tokenService->shouldReceive('get')->with($refreshToken)->once()->andReturn($refreshToken);

    $throttle = Mockery::mock(InvalidTokenThrottleService::class);

    $job = new TestableEsiJob;
    $job->refreshToken = $refreshToken;
    $job->handle($esi, $tokenService, $throttle);

    expect($job->receivedEsi)->toBe($authenticatedEsi);
});

it('catches EsiRateLimitedException and releases the job', function () {
    $esi = Mockery::mock(EsiClient::class);
    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $throttle = Mockery::mock(InvalidTokenThrottleService::class);

    $job = new TestableEsiJob;
    $job->executeCallback = fn () => throw new EsiRateLimitedException(120);

    $job->handle($esi, $tokenService, $throttle);

    expect($job->released)->toBeTrue()
        ->and($job->releasedAfter)->toBe(120);
});

it('catches EsiErrorLimitedException and releases the job', function () {
    $esi = Mockery::mock(EsiClient::class);
    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $throttle = Mockery::mock(InvalidTokenThrottleService::class);

    $job = new TestableEsiJob;
    $job->executeCallback = fn () => throw new EsiErrorLimitedException(60);

    $job->handle($esi, $tokenService, $throttle);

    expect($job->released)->toBeTrue()
        ->and($job->releasedAfter)->toBe(60);
});

it('rethrows unexpected exceptions from executeJob', function () {
    $esi = Mockery::mock(EsiClient::class);
    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $throttle = Mockery::mock(InvalidTokenThrottleService::class);

    $job = new TestableEsiJob;
    $job->executeCallback = fn () => throw new RequestFailedException(
        new ServerException('failed', new Request('get', '/'), new Response(500)),
        new EsiResponse(json_encode([]), [], 'now', 500)
    );

    $job->handle($esi, $tokenService, $throttle);
})->throws(RequestFailedException::class);

it('permanently fails the job when token service throws InvalidRefreshTokenException', function () {
    $esi = Mockery::mock(EsiClient::class);

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $tokenService->shouldReceive('get')
        ->once()
        ->andThrow(new InvalidRefreshTokenException('Token is invalid', 400));

    $refreshToken = testCharacter()->refreshToken;

    $throttle = Mockery::mock(InvalidTokenThrottleService::class);
    $throttle->shouldReceive('hit')->with($refreshToken->character_id)->once()->andReturn(false);

    $job = new TestableEsiJob;
    $job->refreshToken = $refreshToken;
    $job->handle($esi, $tokenService, $throttle);

    expect($job->failed)->toBeTrue()
        ->and($job->failedWith)->toBeInstanceOf(InvalidRefreshTokenException::class);
});

it('calls setContext on RecordingEsiClient before executeJob', function () {
    $esi = Mockery::mock(RecordingEsiClient::class, function (MockInterface $mock) {
        $mock->shouldReceive('setContext')->with('global', null, null, null)->once();
    });

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $throttle = Mockery::mock(InvalidTokenThrottleService::class);

    $job = new TestableEsiJob;
    $job->handle($esi, $tokenService, $throttle);

    expect($job->receivedEsi)->toBe($esi);
});

it('does not soft-delete the token when failure count is below threshold', function () {
    $esi = Mockery::mock(EsiClient::class);

    $refreshToken = testCharacter()->refreshToken;

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $tokenService->shouldReceive('get')
        ->once()
        ->andThrow(new InvalidRefreshTokenException('Token is invalid', 400));

    $throttle = Mockery::mock(InvalidTokenThrottleService::class);
    $throttle->shouldReceive('hit')->with($refreshToken->character_id)->once()->andReturn(false);

    $job = new TestableEsiJob;
    $job->refreshToken = $refreshToken;
    $job->handle($esi, $tokenService, $throttle);

    expect($refreshToken->fresh())->not->toBeNull()
        ->and($refreshToken->fresh()->deleted_at)->toBeNull();
});

it('soft-deletes the token when failure count reaches the threshold', function () {
    $esi = Mockery::mock(EsiClient::class);

    $refreshToken = testCharacter()->refreshToken;

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $tokenService->shouldReceive('get')
        ->once()
        ->andThrow(new InvalidRefreshTokenException('Token is invalid', 400));

    $throttle = Mockery::mock(InvalidTokenThrottleService::class);
    $throttle->shouldReceive('hit')->with($refreshToken->character_id)->once()->andReturn(true);

    $job = new TestableEsiJob;
    $job->refreshToken = $refreshToken;
    $job->handle($esi, $tokenService, $throttle);

    expect($job->failed)->toBeTrue();

    $refreshToken->refresh();
    expect($refreshToken->deleted_at)->not->toBeNull();
});

it('rateLimitGroup returns global when OPERATION_CLASS is empty', function () {
    $job = new TestableEsiJob;

    expect($job->rateLimitGroup())->toBe('global');
});

it('rateLimitGroup returns RATE_LIMIT_GROUP constant from OPERATION_CLASS', function () {
    $job = new TestableEsiJobWithOperation;

    expect($job->rateLimitGroup())->toBe(GetCharactersCharacterIdMail::RATE_LIMIT_GROUP);
});

it('rateLimitCharacterId returns null for public endpoints', function () {
    $job = new TestableEsiJob;

    expect($job->rateLimitCharacterId())->toBeNull();
});

it('rateLimitCharacterId returns character_id from refresh token', function () {
    $job = new TestableEsiJob;
    $job->refreshToken = testCharacter()->refreshToken;

    expect($job->rateLimitCharacterId())->toBe(testCharacter()->character_id);
});

it('uniqueId returns tags joined with comma and space', function () {
    $job = new TestableEsiJob;

    expect($job->uniqueId())->toBe('test, esijob');
});

it('backoff returns array of delay values in seconds', function () {
    $job = new TestableEsiJob;

    expect($job->backoff())->toBe([60, 300, 600, 900, 900, 900, 900, 900, 900]);
});

it('bounds retries by time and genuine errors, not a fixed attempt count', function () {
    $job = new TestableEsiJob;

    // No fixed attempt cap: rate-limit releases (flow control) must never accumulate into
    // MaxAttemptsExceeded. Failure is bounded by genuine errors + a time deadline instead.
    expect($job->tries)->toBe(0)
        ->and($job->maxExceptions)->toBe(3);
});

it('retryUntil returns a deadline 30 minutes out', function () {
    Carbon::setTestNow('2026-07-11 12:00:00');

    $job = new TestableEsiJob;

    expect($job->retryUntil())->toEqual(now()->addMinutes(30));

    Carbon::setTestNow();
});

it('degrades to null quota and the global group for endpoints without rate-limit metadata', function () {
    $job = new TestableEsiJobWithoutRateLimit;

    expect($job->rateLimitMaxTokens())->toBeNull()
        ->and($job->rateLimitWindowSeconds())->toBeNull()
        ->and($job->rateLimitGroup())->toBe('global');
});
