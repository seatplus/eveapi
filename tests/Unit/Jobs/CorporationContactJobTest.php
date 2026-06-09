<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Models\Contacts\Contact;

it('returns early if cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationContactJob(corporationId: 456, characterId: 123);
    $job->executeJob($esi);

    expect(Contact::count())->toBe(0);
});

it('writes contacts to database on normal execution', function () {
    $contact = (object) [
        'contact_id' => 1001,
        'contact_type' => 'character',
        'standing' => 5.0,
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([$contact]));

    $job = new CorporationContactJob(corporationId: 456, characterId: 123);
    $job->executeJob($esi);

    expect(Contact::count())->toBe(1);
});

it('handles multiple pages and writes all contacts', function () {
    $contact = fn (int $id) => (object) [
        'contact_id' => $id,
        'contact_type' => 'character',
        'standing' => 0.0,
    ];

    $page1 = makeEsiRawResponse(makeEsiResult([$contact(1001)], pages: 2));
    $page2 = makeEsiRawResponse(makeEsiResult([$contact(1002)], pages: 2));

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();
    $esi->shouldReceive('invoke')->andReturn($page1, $page2);

    $job = new CorporationContactJob(corporationId: 456, characterId: 123);
    $job->executeJob($esi);

    expect(Contact::count())->toBe(2);
});
