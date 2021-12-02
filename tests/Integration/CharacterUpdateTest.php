<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Models\RefreshToken;

test('if constructor receives single refresh token push update to high queue', function () {
    Queue::fake();

    (new UpdateCharacter(testCharacter()->refresh_token))->handle();

    Queue::assertPushedOn('high', CharacterBatchJob::class);
});

it('it dispatches character batch job', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    expect(RefreshToken::all())->toHaveCount(1);

    (new UpdateCharacter)->handle();

    Queue::assertPushed(CharacterBatchJob::class);
});
