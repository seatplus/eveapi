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
    $mockData = WalletJournal::factory()->count(5)->make();

    $esiData = $mockData->map(fn (WalletJournal $j) => CharactersCharacterIdWalletJournalGetItem::from(
        (object) $j->toArray()
    ))->toArray();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($esiData));

    $job = new CharacterWalletJournalJob(testCharacter()->character_id);
    $job->executeJob($esi);

    foreach ($mockData as $data) {
        $this->assertDatabaseHas('wallet_journals', [
            'wallet_journable_id' => $this->test_character->character_id,
            'id' => $data->id,
        ]);
    }
});
