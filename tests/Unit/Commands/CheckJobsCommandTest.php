<?php

it('writes error, warning and success header', function (array $job_checker_result) {

    $job_checker = mock(\Seatplus\Eveapi\Services\JobChecker::class, function ($mock) use ($job_checker_result) {
        $mock->shouldReceive('checkJob')
            ->andReturn(collect([
                $job_checker_result,
            ]));
    });

    $command = new \Seatplus\Eveapi\Commands\CheckJobsCommand($job_checker);

    $command->handle();

    expect(true)->toBeTrue();

})->with([
    'error' => fn () => [
        'status' => 'error',
        'message' => 'test error message',
    ],
    'warning' => fn () => [
        'status' => 'warning',
        'message' => 'test warning message',
    ],
    'success' => fn () => [
        'status' => 'success',
        'message' => 'test success message',
    ],
]);

it('throws exception if status is unknown', function () {

    $job_checker = mock(\Seatplus\Eveapi\Services\JobChecker::class, function ($mock) {
        $mock->shouldReceive('checkJob')
            ->andReturn(collect([
                [
                    'status' => 'unknown',
                    'message' => 'test unknown message',
                ],
            ]));
    });

    $command = new \Seatplus\Eveapi\Commands\CheckJobsCommand($job_checker);

    $command->handle();

})
    ->throws(\Exception::class, 'Unknown status');
