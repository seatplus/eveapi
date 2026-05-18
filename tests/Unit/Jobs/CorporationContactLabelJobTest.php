<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;

it('returns early if cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('contacts->getCorporationsCorporationIdContactsLabels')->andReturn(makeEsiResult([], isCachedLoad: true));

    $job = mock(CorporationContactLabelJob::class)->makePartial();
    $job->corporation_id = 123;
    $job->executeJob($esi);

    expect(true)->toBeTrue();
});
