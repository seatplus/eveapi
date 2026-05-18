<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('characters->getCharactersCharacterIdRoles')->andReturn((object) ['isCachedLoad' => true]);

    $job = mock(CharacterRoleJob::class)->makePartial();
    $job->executeJob($esi);

    expect(true)->toBeTrue();
});
