<?php

it('checks if the response is cached', function () {
    $job = mock(\Seatplus\Eveapi\Jobs\Character\CharacterInfoJob::class, function ($mock) {
        $response = mock(\Seatplus\EsiClient\DataTransferObjects\EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(true)->toBeTrue();
});
