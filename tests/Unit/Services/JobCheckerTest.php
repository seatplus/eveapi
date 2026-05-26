<?php

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Mockery\MockInterface;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
use Seatplus\Eveapi\Services\FileGetContentsAction;
use Seatplus\Eveapi\Services\JobChecker;

describe('Middleware check', function () {
    it('returns error if ThrottlesExceptionsWithRedis middleware is missing', function () {
        $job = mock(EsiJob::class, function (MockInterface $mock) {
            $mock->shouldReceive('middleware')->andReturn([
                new EsiProactiveRateLimitMiddleware,
            ]);
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('');
        })->makePartial();
        $jobChecker = new JobChecker($fileGetContentsAction);

        $result = $jobChecker->checkJob($job);

        expect($result->first()['status'])->toEqual('error')
            ->and($result->first()['message'])->toContain('ThrottlesExceptionsWithRedis');
    });

    it('returns error if EsiProactiveRateLimitMiddleware middleware is missing', function () {
        $job = mock(EsiJob::class, function (MockInterface $mock) {
            $mock->shouldReceive('middleware')->andReturn([
                new ThrottlesExceptionsWithRedis,
            ]);
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('');
        })->makePartial();
        $jobChecker = new JobChecker($fileGetContentsAction);

        $result = $jobChecker->checkJob($job);

        expect($result->first()['status'])->toEqual('error')
            ->and($result->first()['message'])->toContain('EsiProactiveRateLimitMiddleware');
    });

    it('returns success when all required middlewares are present', function () {
        $job = new AllianceInfoJob(1);

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('isCachedLoad');
        })->makePartial();

        $jobChecker = new JobChecker($fileGetContentsAction);

        $result = $jobChecker->checkJob($job);

        expect($result->first()['status'])->toEqual('success')
            ->and($result->first()['message'])->toEqual('all required middlewares are present');
    });
});

describe('isCachedLoad check', function () {
    it('returns warning when job file does not check isCachedLoad', function () {
        $job = mock(EsiJob::class, function (MockInterface $mock) {
            $mock->shouldReceive('middleware')->andReturn([
                new ThrottlesExceptionsWithRedis,
                new EsiProactiveRateLimitMiddleware,
            ]);
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('some code without the keyword');
        })->makePartial();

        $jobChecker = new JobChecker($fileGetContentsAction);

        $result = $jobChecker->checkJob($job);

        expect($result[1]['status'])->toEqual('warning')
            ->and($result[1]['message'])->toContain('isCachedLoad');
    });

    it('returns success when job file checks isCachedLoad', function () {
        $job = new AllianceInfoJob(1);

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('if ($response->isCachedLoad) return;');
        })->makePartial();

        $jobChecker = new JobChecker($fileGetContentsAction);

        $result = $jobChecker->checkJob($job);

        expect($result[1]['status'])->toEqual('success')
            ->and($result[1]['message'])->toEqual('job checks isCachedLoad');
    });
});
