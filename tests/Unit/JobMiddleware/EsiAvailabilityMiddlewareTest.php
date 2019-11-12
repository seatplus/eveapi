<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Mockery;
use Seatplus\Eveapi\Actions\Esi\GetEsiStatusAction;
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

        $this->middleware = new EsiAvailabilityMiddleware();

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_handles_the_job_if_esi_available()
    {

        $this->mockGetEsiStatusAction('ok');

        $this->job->shouldReceive('fire')->times(1);

        $this->middleware->handle($this->job, $this->next);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_fails_the_job_if_esi_unavailable()
    {

        $this->mockGetEsiStatusAction('not ok');

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

    private function mockGetEsiStatusAction(string $string)
    {

        $mock = Mockery::mock('overload:Seatplus\Eveapi\Actions\Esi\GetEsiStatusAction');
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn($string);
    }

}
