<?php

use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Services\Esi\RetrieveEsiData;


test('it returns client for an unauthenticated request', function () {
    $retrieve = new RetrieveEsiData();

    $request_container = new EsiRequestContainer([
        'method' => 'get',
        'version' => 'v4',
        'endpoint' => 'foo/bar',
    ]);

    $retrieve->setRequest($request_container);
    expect($retrieve->getClient())->toBeInstanceOf(\Seatplus\EsiClient\EsiClient::class);
});

test('it returns client for an authenticated request', function () {

    $esi_array = [
        'eve_client_id' => faker()->sha256,
        'eve_client_secret' => faker()->sha256,
    ];

    config()->set('eveapi.config.esi', $esi_array);
    $refresh_token = \Seatplus\Eveapi\Models\RefreshToken::factory()->create();

    $retrieve = new RetrieveEsiData();

    $request_container = new EsiRequestContainer([
        'method' => 'get',
        'version' => 'v4',
        'endpoint' => 'foo/bar',
        'refresh_token' => $refresh_token,
    ]);

    $retrieve->setRequest($request_container);

    $esi_client = $retrieve->getClient();
    expect($esi_client)->toBeInstanceOf(\Seatplus\EsiClient\EsiClient::class);
    expect($esi_client->getAuthentication())
        ->toBeInstanceOf(\Seatplus\EsiClient\DataTransferObjects\EsiAuthentication::class)
        ->client_id->toBeString()->toBe($esi_array['eve_client_id'])
        ->secret->toBeString()->toBe($esi_array['eve_client_secret']);
})->only();
