<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Models\Wallet\Balance;

beforeEach(function () {
    Queue::fake();
});

test('run wallet balance job', function () {
    $mock_data = Balance::factory()->make();

    mockEsiClient(
        'wallet->getCharactersCharacterIdWallet',
        makeEsiResult($mock_data->balance)
    );

    expect(Balance::all())->toHaveCount(0);

    runJob(new CharacterBalanceJob(testCharacter()->character_id));

    expect(Balance::all())->toHaveCount(1);
});
