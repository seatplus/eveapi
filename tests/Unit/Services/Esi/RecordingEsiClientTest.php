<?php

use Illuminate\Support\Facades\Redis;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Fetcher\GuzzleFetcher;
use Seatplus\Eveapi\Services\Esi\RecordingEsiClient;

beforeEach(function () {
    Redis::del('esi_ratelimit:global:public', 'esi_ratelimit:char-social:12345', 'esi_errorlimit:global');
});

it('records rate limit remaining in redis after invoke', function () {
    $esiResponse = new EsiResponse(
        raw: json_encode([]),
        raw_headers: ['X-Ratelimit-Remaining' => '100'],
        expires: 'now',
        response_code: 200,
    );

    $fetcher = Mockery::mock(GuzzleFetcher::class);
    $fetcher->shouldReceive('call')->andReturn($esiResponse);

    $client = new RecordingEsiClient(null, $fetcher);
    $client->setContext('global', null);
    $client->invoke('get', '/characters/{character_id}/', ['character_id' => 1]);

    $stored = Redis::get('esi_ratelimit:global:public');
    expect(json_decode($stored, true)['remaining'])->toBe(100);
});

it('records rate limit with character context', function () {
    $esiResponse = new EsiResponse(
        raw: json_encode([]),
        raw_headers: ['X-Ratelimit-Remaining' => '42'],
        expires: 'now',
        response_code: 200,
    );

    $fetcher = Mockery::mock(GuzzleFetcher::class);
    $fetcher->shouldReceive('call')->andReturn($esiResponse);

    $client = new RecordingEsiClient(null, $fetcher);
    $client->setContext('char-social', 12345);
    $client->invoke('get', '/characters/{character_id}/', ['character_id' => 1]);

    $stored = Redis::get('esi_ratelimit:char-social:12345');
    expect(json_decode($stored, true)['remaining'])->toBe(42);
});

it('records error limit in redis after invoke', function () {
    $esiResponse = new EsiResponse(
        raw: json_encode([]),
        raw_headers: [
            'X-Esi-Error-Limit-Remain' => '50',
            'X-Esi-Error-Limit-Reset' => '30',
        ],
        expires: 'now',
        response_code: 200,
    );

    $fetcher = Mockery::mock(GuzzleFetcher::class);
    $fetcher->shouldReceive('call')->andReturn($esiResponse);

    $client = new RecordingEsiClient(null, $fetcher);
    $client->invoke('get', '/characters/{character_id}/', ['character_id' => 1]);

    $stored = Redis::get('esi_errorlimit:global');
    expect(json_decode($stored, true)['remaining'])->toBe(50);
});

it('skips rate limit recording when rateLimitRemaining is null', function () {
    $esiResponse = new EsiResponse(
        raw: json_encode([]),
        raw_headers: [],
        expires: 'now',
        response_code: 200,
    );

    $fetcher = Mockery::mock(GuzzleFetcher::class);
    $fetcher->shouldReceive('call')->andReturn($esiResponse);

    $client = new RecordingEsiClient(null, $fetcher);
    $client->invoke('get', '/characters/{character_id}/', ['character_id' => 1]);

    expect(Redis::get('esi_ratelimit:global:public'))->toBeNull();
});

it('skips error limit recording when errorLimitRemaining is null', function () {
    $esiResponse = new EsiResponse(
        raw: json_encode([]),
        raw_headers: ['X-Ratelimit-Remaining' => '80'],
        expires: 'now',
        response_code: 200,
    );

    $fetcher = Mockery::mock(GuzzleFetcher::class);
    $fetcher->shouldReceive('call')->andReturn($esiResponse);

    $client = new RecordingEsiClient(null, $fetcher);
    $client->invoke('get', '/characters/{character_id}/', ['character_id' => 1]);

    expect(Redis::get('esi_errorlimit:global'))->toBeNull();
});
