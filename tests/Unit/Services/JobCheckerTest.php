<?php

use Mockery\MockInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Services\EsiPathService;
use Seatplus\Eveapi\Services\FileGetContentsAction;
use Seatplus\Eveapi\Services\JobChecker;

describe('Version checker', function () {
    it('returns success when version is up to date', function () {
        $esiPathService = mock(EsiPathService::class)->makePartial();
        $esiPathService->shouldReceive('getEsiPaths')->andReturn([
            '/endpoint' => [
                'get' => [
                    'x-alternate-versions' => ['v1', 'v2'],
                ],
            ],
        ]);

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('{"v1": "2021-01-01", "v2": "2021-01-01"}');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$esiPathService, $fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result->first()['message'])->toEqual('version is up to date');
    });

    it('returns warning when version is not up to date', function () {
        $esiPathService = mock(EsiPathService::class)->makePartial();
        $esiPathService->shouldReceive('getEsiPaths')->andReturn([
            '/endpoint' => [
                'get' => [
                    'x-alternate-versions' => ['v1', 'v2'],
                ],
            ],
        ]);

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v1');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('{"v1": "2021-01-01", "v2": "2021-01-01"}');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$esiPathService, $fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result->first()['message'])->toContain('new version is available');
    });

    it('returns error when endpoint is not available', function () {
        $esiPathService = mock(EsiPathService::class)->makePartial();
        $esiPathService->shouldReceive('getEsiPaths')->andReturn([
            '/endpoint' => [
                'get' => [
                    'x-alternate-versions' => ['v2', 'v3'],
                ],
            ],
        ]);

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v1');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('{"v1": "2021-01-01"}');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$esiPathService, $fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result->first()['message'])->toContain('version is outdated');
    });
});

describe('required scope checker', function () {
    it('returns success when no security scope is required', function () {
        $esiPathService = mock(EsiPathService::class)->makePartial();
        $esiPathService->shouldReceive('getEsiPaths')->andReturn([
            '/endpoint' => [
                'get' => [
                    'x-alternate-versions' => ['v1', 'v2'],
                ],
            ],
        ]);

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$esiPathService, $fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        // expect the second result to be success
        expect($result[1]['message'])->toEqual('no security scope required');
    });

    it('returns error if endpoint does not require security but job implements Required Scope Interface', function () {
        $esiPathService = mock(EsiPathService::class)->makePartial();
        $esiPathService->shouldReceive('getEsiPaths')->andReturn([
            '/endpoint' => [
                'get' => [
                    'x-alternate-versions' => ['v1', 'v2'],
                ],
            ],
        ]);

        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasRequiredScopeInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$esiPathService, $fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        // expect the first result to be error
        expect($result[1]['message'])->toEqual('endpoint does not require authentication but job does implement HasRequiredScopeInterface');
    });

    it('returns error if endpoint requires security but job does not implement Required Scope Interface', function () {
        $esiPathService = mock(EsiPathService::class)->makePartial();
        $esiPathService->shouldReceive('getEsiPaths')->andReturn([
            '/endpoint' => [
                'get' => [
                    'x-alternate-versions' => ['v1', 'v2'],
                    'security' => [
                        [
                            'evesso' => ['esi-assets.read_assets.v1'],
                        ],
                    ],
                ],
            ],
        ]);

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$esiPathService, $fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        // expect the first result to be error
        expect($result[1]['message'])->toEqual('job requires authentication but does not implement HasRequiredScopeInterface');
    });

    it('returns error if job does not implement the required scopes for the endpoint', function () {
        $esiPathService = mock(EsiPathService::class)->makePartial();
        $esiPathService->shouldReceive('getEsiPaths')->andReturn([
            '/endpoint' => [
                'get' => [
                    'x-alternate-versions' => ['v1', 'v2'],
                    'security' => [
                        [
                            'evesso' => ['esi-assets.read_assets.v1'],
                        ],
                    ],
                ],
            ],
        ]);

        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasRequiredScopeInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('getRequiredScope')->andReturn('esi-assets.read_assets.v2');
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$esiPathService, $fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        // expect the second result to be error
        expect($result[1]['message'])->toEqual('job requires scope esi-assets.read_assets.v2 but endpoint requires esi-assets.read_assets.v1');
    });

    it('returns success if job implements the required scopes for the endpoint', function () {
        $esiPathService = mock(EsiPathService::class)->makePartial();
        $esiPathService->shouldReceive('getEsiPaths')->andReturn([
            '/endpoint' => [
                'get' => [
                    'x-alternate-versions' => ['v1', 'v2'],
                    'security' => [
                        [
                            'evesso' => ['esi-assets.read_assets.v1'],
                        ],
                    ],
                ],
            ],
        ]);

        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasRequiredScopeInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('getRequiredScope')->andReturn('esi-assets.read_assets.v1');
        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$esiPathService, $fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        // expect the second result to be success
        expect($result[1]['message'])->toEqual('security scope required')
            ->and($result[1]['status'])->toEqual('success');
    });

});

describe('Path Check', function () {

    beforeEach(function () {
        $this->esiPathService = mock(EsiPathService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getEsiPaths')->andReturn([
                '/endpoint/{id}' => [
                    'get' => [
                        'x-alternate-versions' => ['v1', 'v2'],
                    ],
                ],
                '/endpoint/' => [
                    'get' => [
                        'x-alternate-versions' => ['v1', 'v2'],
                    ],
                ],
            ]);
        })->makePartial();
        $this->fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('{"v1": "2021-01-01", "v2": "2021-01-01"}');
        })->makePartial();
    });

    it('returns error if job endpoint has moustache but no HasPathValuesInterface implemented', function () {

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/{id}');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[2]['message'])->toEqual('path values are required but job does not implement HasPathValuesInterface')
            ->and($result[2]['status'])->toEqual('error');

    });

    it('returns success if job endpoint has no moustache and no HasPathValuesInterface implemented', function () {

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[2]['message'])->toEqual('no path values required')
            ->and($result[2]['status'])->toEqual('success');

    });

    it('returns error if no path values are set but have HasPathValuesInterface implemented', function () {

        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasPathValuesInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/{id}');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('getPathValues')->andReturn([]);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[2]['message'])->toEqual('no path values set even though job requires path values')
            ->and($result[2]['status'])->toEqual('error');
    });

    it('returns error if path value is not used in path', function () {
        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasPathValuesInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/{id}');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('getPathValues')->andReturn(['corporation_id' => 1]);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[2]['message'])->toEqual('path value corporation_id is not used in path')
            ->and($result[2]['status'])->toEqual('error');
    });

    it('returns success if path value is used in path', function () {
        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasPathValuesInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/{id}');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('getPathValues')->andReturn(['id' => 1]);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[2]['message'])->toEqual('path values all set')
            ->and($result[2]['status'])->toEqual('success');
    });
});

describe('Middleware check', function () {

    beforeEach(function () {
        $this->esiPathService = mock(EsiPathService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getEsiPaths')->andReturn([
                '/endpoint' => [
                    'get' => [
                        'x-alternate-versions' => ['v1', 'v2'],
                    ],
                ],
            ]);
        })->makePartial();
        $this->fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('{"v1": "2021-01-01", "v2": "2021-01-01"}');
        })->makePartial();
    });

    it('returns error if ThrottlesExceptionsWithRedis middleware is not set', function () {
        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('middleware')->andReturn([]);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[3]['message'])->toEqual('ThrottlesExceptionsWithRedis Middleware is not used')
            ->and($result[3]['status'])->toEqual('error');
    });

    it('returns error if job implements HasRequiredScopeInterface but middleware is not set', function () {
        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasRequiredScopeInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('middleware')->andReturn([
                new \Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis,
            ]);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[3]['message'])->toEqual('HasRequiredScopeMiddleware is not used even though job requires authentication')
            ->and($result[3]['status'])->toEqual('error');
    });

    it('returns success if ThrottlesExceptionsWithRedis middleware is set', function () {
        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('middleware')->andReturn([
                new \Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis,
            ]);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[3]['message'])->toEqual('All required middlewares are used')
            ->and($result[3]['status'])->toEqual('success');
    });
});

describe('Corporation Role Check', function () {
    beforeEach(function () {
        $this->esiPathService = mock(EsiPathService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getEsiPaths')->andReturn([
                '/endpoint/safe' => [
                    'get' => [
                        'x-alternate-versions' => ['v1', 'v2'],
                        'x-required-roles' => ['Director'],
                    ],
                ],
                '/endpoint/unsafe' => [
                    'get' => [
                        'x-alternate-versions' => ['v1', 'v2'],
                    ],
                ],
            ]);
        })->makePartial();
        $this->fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('{"v1": "2021-01-01", "v2": "2021-01-01"}');
        })->makePartial();
    });

    it('returns success if no corporation role is required', function () {
        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/unsafe');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[4]['message'])->toEqual('no corporate roles required')
            ->and($result[4]['status'])->toEqual('success');
    });

    it('returns error if corporation role is required but no HasCorporationRoleInterface implemented', function () {
        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/safe');
            $mock->shouldReceive('getMethod')->andReturn('get');
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[4]['message'])->toEqual('job requires corporation roles but does not implement HasCorporationRoleInterface')
            ->and($result[4]['status'])->toEqual('error');
    });

    it('returns error if corporation role is required but no corporation role is set', function () {
        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasCorporationRoleInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/safe');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('getCorporationRoles')->andReturn([]);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[4]['message'])->toEqual('job requires corporation roles but does not set them')
            ->and($result[4]['status'])->toEqual('error');
    });

    it('returns error if corporation role is required but corporation role is not set', function () {
        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasCorporationRoleInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/safe');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('getCorporationRoles')->andReturn(['Accountant']);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[4]['message'])->toEqual('job requires corporate roles (Director) but Director is not set')
            ->and($result[4]['status'])->toEqual('error');
    });

    it('returns success if corporation role is required and corporation role is set', function () {
        $job = mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasCorporationRoleInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/safe');
            $mock->shouldReceive('getMethod')->andReturn('get');
            $mock->shouldReceive('getCorporationRoles')->andReturn(['Director']);
        })->makePartial();

        $jobChecker = mock(JobChecker::class, [$this->esiPathService, $this->fileGetContentsAction])->makePartial();

        $result = $jobChecker->checkJob($job);

        expect($result[4]['message'])->toEqual('all required corporate roles are set')
            ->and($result[4]['status'])->toEqual('success');
    });
});

describe('is checking cache check', function () {
    beforeEach(function () {
        $this->esiPathService = mock(EsiPathService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getEsiPaths')->andReturn([
                '/endpoint/cached' => [
                    'get' => [
                        'x-alternate-versions' => ['v1', 'v2'],
                        'x-cached-seconds' => 3600,
                    ],
                    'post' => [
                        'x-alternate-versions' => ['v1', 'v2'],
                        'x-cached-seconds' => 3600,
                    ],
                ],
                '/endpoint/not-cached' => [
                    'get' => [
                        'x-alternate-versions' => ['v1', 'v2'],
                    ],
                ],
            ]);
        })->makePartial();
        $this->fileGetContentsAction = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('{"v1": "2021-01-01", "v2": "2021-01-01"}');
        })->makePartial();
    });

    it('returns success when CharacterAffiliationJob is a post request with cached seconds', function () {

        $job = mock(\Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/cached');
            $mock->shouldReceive('getMethod')->andReturn('post');

        })->makePartial();

        $fileGetContentsAction = mock(FileGetContentsAction::class)->makePartial();
        $jobChecker = new JobChecker($this->esiPathService, $this->fileGetContentsAction);

        $result = $jobChecker->checkJob($job);

        expect($result[5]['message'])->toEqual('CharacterAffiliationJob is a post request but has cached seconds')
            ->and($result[5]['status'])->toEqual('success');
    });

    it('returns warning when post request has cached seconds', function () {

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/cached');
            $mock->shouldReceive('getMethod')->andReturn('post');

        })->makePartial();

        $jobChecker = new JobChecker($this->esiPathService, $this->fileGetContentsAction);

        $result = $jobChecker->checkJob($job);

        expect($result[5]['message'])->toEqual('job is a post request but has cached seconds')
            ->and($result[5]['status'])->toEqual('warning');
    });

    it('returns error if endpoint has no cached seconds but isCachedLoad check', function () {

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/not-cached');
            $mock->shouldReceive('getMethod')->andReturn('get');

        })->makePartial();

        $action = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('isCachedLoad()');
        })->makePartial();

        $jobChecker = new JobChecker($this->esiPathService, $action);

        $result = $jobChecker->checkJob($job);

        expect($result[5]['message'])->toEqual('job checks if response is cached but endpoint is not cached')
            ->and($result[5]['status'])->toEqual('error');
    });

    it('returns error if job has cached seconds but no isCachedLoad check', function () {

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/cached');
            $mock->shouldReceive('getMethod')->andReturn('get');

        })->makePartial();

        $jobChecker = new JobChecker($this->esiPathService, $this->fileGetContentsAction);

        $result = $jobChecker->checkJob($job);

        expect($result[5]['message'])->toEqual('job does not check if response is cached but endpoint is cached')
            ->and($result[5]['status'])->toEqual('error');
    });

    it('returns success if job has cached seconds and isCachedLoad check', function () {

        $job = mock(EsiBase::class, function (MockInterface $mock) {
            $mock->shouldReceive('getVersion')->andReturn('v2');
            $mock->shouldReceive('getEndpoint')->andReturn('/endpoint/cached');
            $mock->shouldReceive('getMethod')->andReturn('get');

        })->makePartial();

        $action = mock(FileGetContentsAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn('isCachedLoad()');
        })->makePartial();

        $jobChecker = new JobChecker($this->esiPathService, $action);

        $result = $jobChecker->checkJob($job);

        expect($result[5]['message'])->toEqual('job checks if response is cached and endpoint is cached')
            ->and($result[5]['status'])->toEqual('success');
    });
});
