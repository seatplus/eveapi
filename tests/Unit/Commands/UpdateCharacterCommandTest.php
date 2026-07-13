<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;

it('dispatches a high-priority UpdateCharacter for the given character', function () {
    Queue::fake();

    $characterId = testCharacter()->character_id;

    $this->artisan('seatplus:update-character', ['character_id' => $characterId])
        ->assertExitCode(0);

    Queue::assertPushedOn('high', UpdateCharacter::class);
});

it('fails when no refresh token exists for the character', function () {
    Queue::fake();

    $this->artisan('seatplus:update-character', ['character_id' => 999999999])
        ->assertExitCode(1);

    Queue::assertNotPushed(UpdateCharacter::class);
});
