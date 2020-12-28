<?php

namespace Seatplus\Eveapi\Tests\Unit\JobMiddleware;

use Mockery;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class EsiRateLimitedMiddlewareTest extends TestCase
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

        $this->middleware = Mockery::mock(EsiRateLimitedMiddleware::class)
            ->makePartial();

    }

    /** @test */
    public function it_handles_the_job_if_not_ratelimited()
    {

        $this->job->shouldReceive('fire')->times(1);
        $this->middleware->shouldReceive('isEsiRateLimited')->andReturn(false);

        $this->middleware->handle($this->job, $this->next);
    }

    /** @test */
    public function it_fails_the_job_if_not_ratelimited()
    {

        $this->job->shouldReceive('fail')->times(1);
        $this->middleware->shouldReceive('isEsiRateLimited')->andReturn(true);

        $this->middleware->handle($this->job, $this->next);
    }


    private function mockJob()
    {

        $this->job = Mockery::mock();

        $this->next = function ($job) {
            $job->fire();
        };

    }

}
