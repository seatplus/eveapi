<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Models\Wallet\Balance;

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();
});

test('run wallet journal job', function () {
    $mock_data = Balance::factory()->make();

    mockRetrieveEsiDataAction(['scalar' => $mock_data->balance]);

    expect(Balance::all())->toHaveCount(0);

    $job = new CharacterBalanceJob(testCharacter()->character_id);

    $job->handle();

    expect(Balance::all())->toHaveCount(1);
});
