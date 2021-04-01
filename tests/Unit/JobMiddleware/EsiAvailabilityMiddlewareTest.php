<?php

namespace Seatplus\Eveapi\Tests\Unit\JobMiddleware;

use Mockery;
use Seatplus\Eveapi\Esi\Esi\GetEsiStatusAction;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class EsiAvailabilityMiddlewareTest extends TestCase
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

        $this->middleware = Mockery::mock(EsiAvailabilityMiddleware::class)
            ->makePartial();

        $this->markTestSkipped('to be deleted middleware');

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_handles_the_job_if_esi_available()
    {

        $this->middleware->status = 'ok';

        $this->job->shouldReceive('fire')->times(1);


        $this->middleware->handle($this->job, $this->next);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_fails_the_job_if_esi_unavailable()
    {

        $this->middleware->status = 'not ok';

        $this->job->shouldReceive('fail')->times(1);

        $this->middleware->handle($this->job, $this->next);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_fails_the_job_if_esi_is_rate_limited()
    {

        $this->middleware->shouldReceive('isEsiRateLimited')->andReturnTrue();

        $this->job->shouldReceive('fail')->times(1);

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
