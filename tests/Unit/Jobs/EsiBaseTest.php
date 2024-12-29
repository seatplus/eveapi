<?php

it('has backoff array', function () {
    $job = mock(\Seatplus\Eveapi\Jobs\EsiBase::class)->makePartial();

    expect($job->backoff())->toBeArray();
});

it('reports exception', function () {
    $job = mock(\Seatplus\Eveapi\Jobs\EsiBase::class, function (\Mockery\MockInterface $mock) {
        $mock->shouldReceive('executeJob')
            ->andThrow(new \Exception('test'));

    })->makePartial();

    // act
    $job->handle();


})
    ->throws(\Exception::class, 'test');
