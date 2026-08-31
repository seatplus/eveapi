<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Killmails\KillmailJob;

it('fails when server exception is handled', function () {
    $exception = new RequestFailedException(
        new Exception('failed', 500),
        new EsiResponse(json_encode([]), [], 'now', 200)
    );

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $exception);

    $job = new CharacterAssetJob(testCharacter()->character_id);
    $job->executeJob($esi);
})->throws(RequestFailedException::class);

it('fails when client exception is handled', function () {
    $exception = new RequestFailedException(
        new Exception('failed', 404),
        new EsiResponse(json_encode([]), [], 'now', 200)
    );

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $exception);

    $job = new CharacterAssetJob(testCharacter()->character_id);
    $job->executeJob($esi);
})->throws(RequestFailedException::class);

it('base class getRefreshToken returns null for public endpoints', function () {
    $job = new KillmailJob(123, 'asd');

    expect($job->getRefreshToken())->toBeNull();
});
