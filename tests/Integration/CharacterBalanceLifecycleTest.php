<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Models\Wallet\Balance;

beforeEach(function () {
    Queue::fake();
});

test('run wallet balance job', function () {
    $mockData = Balance::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($mockData->balance));

    expect(Balance::all())->toHaveCount(0);

    $job = new CharacterBalanceJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Balance::all())->toHaveCount(1);
});
