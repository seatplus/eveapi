<?php

namespace Seatplus\Eveapi\Tests\Unit\JobMiddleware;

use Mockery;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class HasRequiredScopeMiddlewareTest extends TestCase
{
    /**
     * @var \Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware
     */
    private $middleware;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $job;

    /**
     * @var \Closure
     */
    private $next;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $actionClass;

    public function setUp(): void
    {

        parent::setUp();

        $this->mockJob();
        $this->mockGetActionClass();
        $this->middleware = new HasRequiredScopeMiddleware();

    }

    /** @test */
    public function it_runs_with_required_scope()
    {


        $this->job->shouldReceive('fire')->times(1);
        $this->job->shouldReceive('getActionClass')->andReturn($this->actionClass);

        $this->actionClass->shouldReceive('getRequiredScope')->andReturn('someScope');;

        $this->job->refresh_token = factory(RefreshToken::class)->make([
            'scopes' => ['someScope']
        ]);

        $this->middleware->handle($this->job, $this->next);
    }

    /** @test */
    public function it_fails_without_required_scope()
    {

        $this->job->shouldReceive('fail')->times(1);
        $this->job->shouldReceive('getActionClass')->andReturn($this->actionClass);

        $this->actionClass->shouldReceive('getRequiredScope')->andReturn('someScope');

        $this->job->refresh_token = factory(RefreshToken::class)->make([
            'scopes' => ['someDifferentScope']
        ]);

        $this->middleware->handle($this->job, $this->next);
    }


    private function mockJob()
    {

        $this->job = Mockery::mock();

        $this->next = function ($job) {
            $job->fire();
        };

    }

    private function mockGetActionClass()
    {
        $this->actionClass = Mockery::mock();
    }

}
