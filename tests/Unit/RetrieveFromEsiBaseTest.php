<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Exceptions\RequestFailedException;

it('fails when server exception is handled', function () {

    $exception = new RequestFailedException(
        new \GuzzleHttp\Exception\ServerException('failed', new \GuzzleHttp\Psr7\Request('get', 'now'), new \GuzzleHttp\Psr7\Response(404)),
        new EsiResponse(json_encode([]), [], 'now', 200)
    );

    \Seatplus\Eveapi\Services\Facade\RetrieveEsiData::shouldReceive('execute')->andThrow($exception);

    $job = new \Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob(123);
    $job->executeJob();

    expect(true)->toBeTrue();
})->throws(RequestFailedException::class);

it('fails when client exception is handled', function () {

    $exception = new RequestFailedException(
        new \GuzzleHttp\Exception\ClientException('failed', new \GuzzleHttp\Psr7\Request('get', 'now'), new \GuzzleHttp\Psr7\Response(404)),
        new EsiResponse(json_encode([]), [], 'now', 200)
    );

    \Seatplus\Eveapi\Services\Facade\RetrieveEsiData::shouldReceive('execute')->andThrow($exception);

    $job = new \Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob(123);
    $job->executeJob();

    expect(true)->toBeTrue();
})->throws(RequestFailedException::class);
