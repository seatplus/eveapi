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
    mockJob(NewEsiBase::class);

    $this->job->shouldReceive('fail')->times(1);
    $this->job->shouldReceive('getRequiredScope')->andReturn('someScope');

    $this->job->refresh_token = RefreshToken::factory()->make([
        'scopes' => ['someDifferentScope'],
    ]);

    $this->middleware->handle($this->job, $this->next);
});

// Helpers
function mockJob(string $base_class)
{
    $this->job = Mockery::mock($base_class);

    $this->next = function ($job) {
        $job->fire();
    };
}
