<?php

it('checks if the response is cached', function () {
    $job = mock(\Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob::class, function ($mock) {
        $response = mock(\Seatplus\EsiClient\DataTransferObjects\EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(\Seatplus\Eveapi\Models\Assets\Asset::count())->toBe(0);
});

it('increments page', function () {
    Queue::fake();

    $asset = \Seatplus\Eveapi\Models\Assets\Asset::factory()->count(2)->make();

    $response1 = new \Seatplus\EsiClient\DataTransferObjects\EsiResponse(json_encode($asset->toArray()), ['X-Pages' => 2], 'now', 200);
    $response2 = new \Seatplus\EsiClient\DataTransferObjects\EsiResponse('{}', ['X-Pages' => 2], 'now', 200);

    $job = mock(\Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob::class)->makePartial();
    $job->__construct(123);
    $job->shouldReceive('retrieve')->twice()->andReturns($response1, $response2);

    $job->executeJob();

    expect($job->getPage())->toEqual(2);


});
