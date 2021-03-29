<?php

namespace Seatplus\Eveapi\Tests\Unit\JobMiddleware;

use Mockery;
use Seatplus\Eveapi\Esi\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
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

        $this->job->refresh_token = RefreshToken::factory()->make([
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

        $this->job->refresh_token = RefreshToken::factory()->make([
            'scopes' => ['someDifferentScope']
        ]);

        $this->middleware->handle($this->job, $this->next);
    }

    /** @test */
    public function it_fails_without_required_scope_on_NewEsiBase_jobs()
    {

        $this->mockJob(NewEsiBase::class);

        $this->job->shouldReceive('fail')->times(1);
        $this->job->shouldReceive('getRequiredScope')->andReturn('someScope');

        $this->job->refresh_token = RefreshToken::factory()->make([
            'scopes' => ['someDifferentScope']
        ]);

        $this->middleware->handle($this->job, $this->next);
    }


    private function mockJob($base_class = EsiBase::class)
    {

        $this->job = Mockery::mock($base_class);

        $this->next = function ($job) {
            $job->fire();
        };

    }

    private function mockGetActionClass()
    {
        $this->actionClass = Mockery::mock( RetrieveFromEsiInterface::class);
    }

}
