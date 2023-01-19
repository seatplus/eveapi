<?php

use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;

beforeEach(function () {
    $this->middleware = new HasRequiredScopeMiddleware();
});

it('fails without required scope on new esi base jobs', function () {
    $this->job = Mockery::mock(EsiBase::class);

    $this->next = function ($job) {
        $job->fire();
    };

    $this->job->shouldReceive('fail')->times(1);
    $this->job->shouldReceive('getRequiredScope')->andReturn('someScope');

    $this->job->shouldReceive('getRefreshToken')->andReturn(RefreshToken::factory()->make([
        'scopes' => ['someDifferentScope'],
    ]));

    $this->middleware->handle($this->job, $this->next);
});
