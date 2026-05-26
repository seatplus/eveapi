<?php

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;

it('fails when server exception is handled', function () {
    $exception = new RequestFailedException(
        new ServerException('failed', new Request('get', 'now'), new Response(404)),
        new EsiResponse(json_encode([]), [], 'now', 200)
    );

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $exception);

    $job = new CharacterAssetJob(testCharacter()->character_id);
    $job->executeJob($esi);
})->throws(RequestFailedException::class);

it('fails when client exception is handled', function () {
    $exception = new RequestFailedException(
        new ClientException('failed', new Request('get', 'now'), new Response(404)),
        new EsiResponse(json_encode([]), [], 'now', 200)
    );

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $exception);

    $job = new CharacterAssetJob(testCharacter()->character_id);
    $job->executeJob($esi);
})->throws(RequestFailedException::class);
