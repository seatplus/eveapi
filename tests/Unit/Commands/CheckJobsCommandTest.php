<?php

use Seatplus\Eveapi\Commands\CheckJobsCommand;
use Seatplus\Eveapi\Services\JobChecker;

it('writes error, warning and success header', function (array $jobCheckerResult, bool $expectSuccess) {

    $jobChecker = mock(JobChecker::class, function ($mock) use ($jobCheckerResult) {
        $mock->shouldReceive('checkJob')
            ->andReturn(collect([
                $jobCheckerResult,
            ]));
    });

    $this->app->instance(JobChecker::class, $jobChecker);

    $artisan = $this->artisan(CheckJobsCommand::class);

    if ($expectSuccess) {
        $artisan->assertSuccessful();
    } else {
        $artisan->assertFailed();
    }

})->with([
    'error' => fn () => [
        ['status' => 'error', 'message' => 'test error message'],
        false,
    ],
    'warning' => fn () => [
        ['status' => 'warning', 'message' => 'test warning message'],
        true,
    ],
    'success' => fn () => [
        ['status' => 'success', 'message' => 'test success message'],
        true,
    ],
]);

it('throws exception if status is unknown', function () {

    $jobChecker = mock(JobChecker::class, function ($mock) {
        $mock->shouldReceive('checkJob')
            ->andReturn(collect([
                [
                    'status' => 'unknown',
                    'message' => 'test unknown message',
                ],
            ]));
    });

    $this->app->instance(JobChecker::class, $jobChecker);

    $this->artisan(CheckJobsCommand::class);

})
    ->throws(Exception::class, 'Unknown status');
