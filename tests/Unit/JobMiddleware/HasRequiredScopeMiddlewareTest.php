<?php

use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;

it('passes the middleware if HasRequiredScopeInterface is not implemented', function () {
    $middleware = new HasRequiredScopeMiddleware();

    $job = Mockery::mock(EsiBase::class);

    $job->shouldReceive('fail')->times(0);
    $job->shouldReceive('fire')->times(1);

    $next = function ($job) {
        $job->fire();
    };

    $middleware->handle($job, $next);
});

it('fails the job if required scope is not set', function () {
    [$this->middleware, $this->job, $this->next] = prepareJobMiddleware();

    $this->job->shouldReceive('getRequiredScope')->andThrow(new Exception);

    $this->middleware->handle($this->job, $this->next);
});

it('fails the job if refresh_token for required scope is not found', function () {
    [$this->middleware, $this->job, $this->next] = prepareJobMiddleware();

    $this->job->shouldReceive('getRequiredScope')->andReturn('some_scope');

    $this->middleware->handle($this->job, $this->next);
});

it('passes the job if refresh_token for required scope is found', function () {
    [$this->middleware, $this->job, $this->next] = prepareJobMiddleware(false);

    $this->job->shouldReceive('getRefreshToken')->andReturn(RefreshToken::factory()->make());

    $this->middleware->handle($this->job, $this->next);
});

function prepareJobMiddleware(bool $should_fail = true)
{
    $middleware = new HasRequiredScopeMiddleware();

    $job = Mockery::mock(EsiBase::class, \Seatplus\Eveapi\Esi\HasRequiredScopeInterface::class);

    if ($should_fail) {
        $job->shouldReceive('fail')->times(1);
        $job->shouldReceive('fire')->times(0);
    } else {
        $job->shouldReceive('fail')->times(0);
        $job->shouldReceive('fire')->times(1);
    }

    $next = function ($job) {
        $job->fire();
    };

    return [$middleware, $job, $next];
}
