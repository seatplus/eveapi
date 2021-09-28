<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('can set propperty test', function () {
    $job = new JobContainer([
        'character_id' => 12,
    ]);

    expect($job->character_id)->toEqual(12);
});

/** @throws \Seatplus\Eveapi\Exceptions\InvalidContainerDataException
 */
test('can not set propperty test', function () {
    $this->expectException(\Spatie\DataTransferObject\Exceptions\UnknownProperties::class);

    new JobContainer([
        'herpaderp' => 'v4',
    ]);
});

test('get character id via propperty', function () {
    $job = new JobContainer([
        'character_id' => 12,
    ]);

    expect($job->getCharacterId())->toEqual(12);
});

test('get character id via refresh token', function () {
    Event::fake();

    $refresh_token = RefreshToken::factory()->create([
        'expires_on' => now()->addDay(),
    ]);

    $job = new JobContainer([
        'refresh_token' => $refresh_token,
    ]);

    expect($job->getCharacterId())->toEqual($refresh_token->character_id);
});
