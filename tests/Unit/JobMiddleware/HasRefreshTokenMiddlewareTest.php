<?php

use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {

    $this->middleware = new HasRefreshTokenMiddleware();

    $this->job = Mockery::mock();

    $this->next = function ($job) {
        $job->fire();
    };
});

it('runs with refresh token', function () {
    $this->job->shouldReceive('fire')->times(1);

    $this->job->refresh_token = RefreshToken::factory()->make();

    $this->middleware->handle($this->job, $this->next);
});

it('fails without refresh token', function () {
    $this->job->shouldReceive('fail')->times(1);

    $this->job->refresh_token = null;

    $this->middleware->handle($this->job, $this->next);
});


