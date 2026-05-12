<?php

use Mockery\MockInterface;
use Seatplus\Eveapi\Jobs\EsiBase;

it('has backoff array', function () {
    $job = mock(EsiBase::class)->makePartial();

    expect($job->backoff())->toBeArray();
});

it('reports exception', function () {
    $job = mock(EsiBase::class, function (MockInterface $mock) {
        $mock->shouldReceive('executeJob')
            ->andThrow(new Exception('test'));

    })->makePartial();

    // act
    $job->handle();

})
    ->throws(Exception::class, 'test');
