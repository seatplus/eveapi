<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Models\RefreshToken;

it('generates an event', function () {
    Event::fake();

    $refreshToken = RefreshToken::factory()->create();

    Event::assertDispatched(RefreshTokenCreated::class, fn ($e) => $e->refreshToken === $refreshToken);
});

it('queues update character job', function () {
    Queue::fake();

    $refreshToken = RefreshToken::factory()->create();

    Queue::assertPushedOn('high', UpdateCharacter::class);
});

it('queues update character job after scope change', function () {
    $refreshToken = Event::fakeFor(fn () => RefreshToken::factory()->scopes(['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1'])->create());

    Queue::fake();

    $helperToken = RefreshToken::factory()->scopes(['public'])->make();

    $refreshToken->token = $helperToken->token;
    $refreshToken->save();

    Queue::assertPushedOn('high', UpdateCharacter::class);
});

it('does not queues update character job after no scope change', function () {
    $refreshToken = Event::fakeFor(fn () => RefreshToken::factory()->scopes(['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1'])->create());

    Queue::fake();

    $helperToken = RefreshToken::factory()->scopes(['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1'])->make();

    $refreshToken->token = $helperToken->token;
    $refreshToken->save();

    Queue::assertNotPushed(UpdateCharacter::class);
});

it('queues update corporation job after scope change', function () {
    $refreshToken = $this->test_character->refreshToken;

    Queue::fake();

    $token = updateRefreshTokenScopes($refreshToken, ['updating']);
    $token->save();

    Queue::assertPushedOn('high', UpdateCorporation::class);
});
