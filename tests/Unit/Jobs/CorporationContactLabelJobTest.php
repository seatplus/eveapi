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
