<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

beforeEach(function () {
    Queue::fake();
});

test('run wallet journal job', function () {
    $mock_data = WalletJournal::factory()->count(5)->make();

    $esi_data = $mock_data->map(fn ($j) => (object) [
        'id' => $j->id,
        'date' => $j->date,
        'description' => $j->description,
        'ref_type' => $j->ref_type,
        'amount' => $j->amount,
        'balance' => $j->balance,
        'context_id' => null,
        'context_id_type' => null,
        'first_party_id' => $j->first_party_id,
        'second_party_id' => $j->second_party_id,
        'reason' => $j->reason,
        'tax' => null,
        'tax_receiver_id' => null,
    ])->toArray();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($esi_data));

    $job = new CharacterWalletJournalJob(testCharacter()->character_id);
    $job->executeJob($esi);

    foreach ($mock_data as $data) {
        $this->assertDatabaseHas('wallet_journals', [
            'wallet_journable_id' => $this->test_character->character_id,
            'id' => $data->id,
        ]);
    }
});
