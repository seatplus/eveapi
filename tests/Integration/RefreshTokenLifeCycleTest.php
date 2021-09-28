<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

it('generates an event', function () {
    Event::fake();

    $refresh_token = RefreshToken::factory()->create();

    Event::assertDispatched(RefreshTokenCreated::class, function ($e) use ($refresh_token) {
        return $e->refresh_token === $refresh_token;
    });
});

it('queues update character job', function () {
    Queue::fake();

    $refresh_token = RefreshToken::factory()->create();

    Queue::assertPushedOn('high', UpdateCharacter::class);
});

it('queues update character job after scope change', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->scopes(['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1'])->create();
    });

    Queue::fake();

    $refresh_token->token = ['updating'];
    $refresh_token->save();

    Queue::assertPushedOn('high', UpdateCharacter::class);
});

it('queues update corporation job after scope change', function () {
    $refresh_token = $this->test_character->refresh_token;

    Queue::fake();

    updateRefreshTokenScopes($refresh_token, ['updating']);
    $refresh_token->save();

    Queue::assertPushedOn('high', UpdateCorporation::class);
});
