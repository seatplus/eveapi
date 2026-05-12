<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Models\Assets\Asset;

it('checks if the response is cached', function () {
    $job = mock(CharacterAssetJob::class, function ($mock) {
        $response = mock(EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(Asset::count())->toBe(0);
});

it('increments page', function () {
    Queue::fake();

    $asset = Asset::factory()->count(2)->make();

    $response1 = new EsiResponse(json_encode($asset->toArray()), ['X-Pages' => 2], 'now', 200);
    $response2 = new EsiResponse('{}', ['X-Pages' => 2], 'now', 200);

    $job = mock(CharacterAssetJob::class)->makePartial();
    $job->__construct(123);
    $job->shouldReceive('retrieve')->twice()->andReturns($response1, $response2);

    $job->executeJob();

    expect($job->getPage())->toEqual(2);

});
