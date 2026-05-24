<?php

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery\MockInterface;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\EsiErrorLimitedException;
use Seatplus\EsiClient\Exceptions\EsiRateLimitedException;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\EsiSchema\Resources\Mail\GetCharactersCharacterIdMail;
use Seatplus\Eveapi\Services\Esi\GetUpToDateRefreshTokenService;
use Seatplus\Eveapi\Services\Esi\RecordingEsiClient;
use Seatplus\Eveapi\Tests\Unit\Jobs\Support\TestableEsiJob;
use Seatplus\Eveapi\Tests\Unit\Jobs\Support\TestableEsiJobWithOperation;

it('calls executeJob via handle', function () {
    $esi = Mockery::mock(EsiClient::class);
    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);

    $job = new TestableEsiJob;
    $job->handle($esi, $tokenService);

    expect($job->executed)->toBeTrue();
});

it('does not apply auth for public endpoints', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldNotReceive('withToken');

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $tokenService->shouldNotReceive('get');

    $job = new TestableEsiJob;
    $job->handle($esi, $tokenService);
});

it('injects auth token when getRefreshToken returns a token', function () {
    $refreshToken = testCharacter()->refresh_token;

    $authenticatedEsi = Mockery::mock(EsiClient::class);

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')
        ->with($refreshToken->getRawOriginal('token'))
        ->once()
        ->andReturn($authenticatedEsi);

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);
    $tokenService->shouldReceive('get')->with($refreshToken)->once()->andReturn($refreshToken);

    $job = new TestableEsiJob;
    $job->refreshToken = $refreshToken;
    $job->handle($esi, $tokenService);

    expect($job->receivedEsi)->toBe($authenticatedEsi);
});

it('catches EsiRateLimitedException and releases the job', function () {
    $esi = Mockery::mock(EsiClient::class);
    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);

    $job = new TestableEsiJob;
    $job->executeCallback = fn () => throw new EsiRateLimitedException(120);

    $job->handle($esi, $tokenService);

    expect($job->released)->toBeTrue()
        ->and($job->releasedAfter)->toBe(120);
});

it('catches EsiErrorLimitedException and releases the job', function () {
    $esi = Mockery::mock(EsiClient::class);
    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);

    $job = new TestableEsiJob;
    $job->executeCallback = fn () => throw new EsiErrorLimitedException(60);

    $job->handle($esi, $tokenService);

    expect($job->released)->toBeTrue()
        ->and($job->releasedAfter)->toBe(60);
});

it('rethrows unexpected exceptions from executeJob', function () {
    $esi = Mockery::mock(EsiClient::class);
    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);

    $job = new TestableEsiJob;
    $job->executeCallback = fn () => throw new RequestFailedException(
        new ServerException('failed', new Request('get', '/'), new Response(500)),
        new EsiResponse(json_encode([]), [], 'now', 500)
    );

    $job->handle($esi, $tokenService);
})->throws(RequestFailedException::class);

it('calls setContext on RecordingEsiClient before executeJob', function () {
    $esi = Mockery::mock(RecordingEsiClient::class, function (MockInterface $mock) {
        $mock->shouldReceive('setContext')->with('global', null)->once();
    });

    $tokenService = Mockery::mock(GetUpToDateRefreshTokenService::class);

    $job = new TestableEsiJob;
    $job->handle($esi, $tokenService);

    expect($job->receivedEsi)->toBe($esi);
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
    $job->refreshToken = testCharacter()->refresh_token;

    expect($job->rateLimitCharacterId())->toBe(testCharacter()->character_id);
});

it('uniqueId returns tags joined with comma and space', function () {
    $job = new TestableEsiJob;

    expect($job->uniqueId())->toBe('test, esijob');
});
