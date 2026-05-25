<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Models\Contacts\Label;

it('returns early if cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationContactLabelJob(corporation_id: 123, character_id: 456);
    $job->executeJob($esi);

    expect(Label::count())->toBe(0);
});

it('writes labels to database on normal execution', function () {
    $label = (object) ['label_id' => 1001, 'label_name' => 'Test Label'];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([$label]));

    $job = new CorporationContactLabelJob(corporation_id: 123, character_id: 456);
    $job->executeJob($esi);

    expect(Label::count())->toBe(1);
});

it('handles multiple pages and writes all labels', function () {
    $label1 = (object) ['label_id' => 1001, 'label_name' => 'Page One Label'];
    $label2 = (object) ['label_id' => 1002, 'label_name' => 'Page Two Label'];

    $page1 = makeEsiRawResponse(makeEsiResult([$label1], pages: 2));
    $page2 = makeEsiRawResponse(makeEsiResult([$label2], pages: 2));

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();
    $esi->shouldReceive('invoke')->andReturn($page1, $page2);

    $job = new CorporationContactLabelJob(corporation_id: 123, character_id: 456);
    $job->executeJob($esi);

    expect(Label::count())->toBe(2);
});
