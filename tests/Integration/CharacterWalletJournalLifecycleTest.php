<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Responses\CharactersCharacterIdWalletJournalGetItem;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

beforeEach(function () {
    Queue::fake();
});

test('run wallet journal job', function () {
    $mock_data = WalletJournal::factory()->count(5)->make();

    $esi_data = $mock_data->map(fn (WalletJournal $j) => CharactersCharacterIdWalletJournalGetItem::from(
        (object) $j->toArray()
    ))->toArray();

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
