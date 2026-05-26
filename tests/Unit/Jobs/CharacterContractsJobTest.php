<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Responses\CharactersCharacterIdContractsGetItem;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;

test('returns early if cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterContractsJob(1);
    $job->executeJob($esi);

    expect(Contract::count())->toBe(0);
});

it('handles multiple pages and writes all contracts', function () {
    Queue::fake();

    $contract = fn (int $id) => CharactersCharacterIdContractsGetItem::from((object) [
        'contract_id' => $id,
        'acceptor_id' => 0,
        'assignee_id' => 0,
        'availability' => 'personal',
        'date_expired' => now()->addDays(7)->toIso8601String(),
        'date_issued' => now()->toIso8601String(),
        'for_corporation' => false,
        'issuer_corporation_id' => 1000001,
        'issuer_id' => 123,
        'status' => 'outstanding',
        'type' => 'item_exchange',
    ]);

    $page1 = makeEsiRawResponse(makeEsiResult([$contract(1001)], pages: 2));
    $page2 = makeEsiRawResponse(makeEsiResult([$contract(1002)], pages: 2));

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();
    $esi->shouldReceive('invoke')->andReturn($page1, $page2);

    $job = new CharacterContractsJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Contract::count())->toBe(2);
});

it('adds follow up jobs to batch if batching', function () {

    $contract = Contract::factory()->count(2)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(
        array_map(fn ($c) => (object) $c, $contract->toArray())
    ));

    $job = mock(CharacterContractsJob::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $job->character_id = 1;
    $job->shouldReceive('batching')->once()->andReturnTrue();
    $job->shouldReceive('batch->add')->once();

    $job->executeJob($esi);

    Queue::assertNothingPushed();
});

it('returns the refresh token', function () {
    $token = RefreshToken::factory()->create();

    $job = new CharacterContractsJob($token->character_id);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});
