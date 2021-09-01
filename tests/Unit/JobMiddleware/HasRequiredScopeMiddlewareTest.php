<?php

use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->middleware = new HasRequiredScopeMiddleware();
});

it('fails without required scope on new esi base jobs', function () {

    $this->job = Mockery::mock(NewEsiBase::class);

    $this->next = function ($job) {
        $job->fire();
    };

    $this->job->shouldReceive('fail')->times(1);
    $this->job->shouldReceive('getRequiredScope')->andReturn('someScope');

    $this->job->refresh_token = RefreshToken::factory()->make([
        'scopes' => ['someDifferentScope'],
    ]);

    $this->middleware->handle($this->job, $this->next);
});

