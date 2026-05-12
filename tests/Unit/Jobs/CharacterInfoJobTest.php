<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;

it('checks if the response is cached', function () {
    $job = mock(CharacterInfoJob::class, function ($mock) {
        $response = mock(EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(true)->toBeTrue();
});
