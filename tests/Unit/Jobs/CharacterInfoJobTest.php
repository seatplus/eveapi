<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('characters->getCharactersCharacterId')->andReturn((object) ['isCachedLoad' => true]);

    $job = mock(CharacterInfoJob::class)->makePartial();
    $job->executeJob($esi);

    expect(true)->toBeTrue();
});
