<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;

it('returns early if cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationContactLabelJob(corporation_id: 123, character_id: 456);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

    expect(true)->toBeTrue();
});
