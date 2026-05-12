<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;

it('checks if the response is cached', function () {
    $job = mock(CharacterRoleJob::class, function ($mock) {
        $response = mock(EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(true)->toBeTrue();
});
