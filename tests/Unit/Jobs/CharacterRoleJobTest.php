<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterRoleJob(12345);
    $job->executeJob($esi);

    expect(CharacterRole::where('character_id', 12345)->count())->toBe(0);
});
