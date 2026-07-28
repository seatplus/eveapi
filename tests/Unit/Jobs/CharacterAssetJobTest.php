<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Responses\CharactersCharacterIdAssetsGetItem;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Models\Assets\Asset;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterAssetJob(12345);
    $job->executeJob($esi);

    expect(Asset::count())->toBe(0);
});

it('handles multiple pages and upserts all assets', function () {
    Queue::fake();

    $asset = fn (int $itemId) => CharactersCharacterIdAssetsGetItem::from((object) [
        'item_id' => $itemId,
        'is_singleton' => false,
        'location_flag' => 'Hangar',
        'location_id' => 60000001,
        'location_type' => 'station',
        'quantity' => 1,
        'type_id' => 34,
    ]);

    $page1 = makeEsiRawResponse(makeEsiResult([$asset(1001)], pages: 2));
    $page2 = makeEsiRawResponse(makeEsiResult([$asset(1002)], pages: 2));

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();
    $esi->shouldReceive('invoke')->andReturn($page1, $page2);

    $job = new CharacterAssetJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Asset::count())->toBe(2);
});

it('runs its write outside the whole-job transaction', function () {
    $job = new CharacterAssetJob(12345);

    expect((fn () => $this->wrapExecuteJobInTransaction())->call($job))->toBeFalse();
});
