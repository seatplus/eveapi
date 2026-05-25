<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalByDivisionJob;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

it('returns early if cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationWalletJournalByDivisionJob(corporation_id: 456, division: 1);
    $job->executeJob($esi);

    expect(WalletJournal::count())->toBe(0);
});

it('handles multiple pages and writes all journal entries', function () {
    $entry = fn (int $id) => (object) [
        'context_id_type' => 'character_id',
        'context_id' => 11111,
        'id' => $id,
        'date' => now(),
        'description' => 'test',
        'ref_type' => 'player_trading',
        'amount' => 100.0,
        'balance' => 200.0,
        'first_party_id' => null,
        'second_party_id' => null,
        'reason' => null,
        'tax' => null,
        'tax_receiver_id' => null,
    ];

    $page1 = makeEsiRawResponse(makeEsiResult([$entry(111)], pages: 2));
    $page2 = makeEsiRawResponse(makeEsiResult([$entry(222)], pages: 2));

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();
    $esi->shouldReceive('invoke')->andReturn($page1, $page2);

    $job = new CorporationWalletJournalByDivisionJob(corporation_id: 456, division: 1);
    $job->executeJob($esi);

    expect(WalletJournal::count())->toBe(2);
});
