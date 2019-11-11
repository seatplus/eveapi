<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Mockery;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class HasRefreshTokenMiddlewareTest extends TestCase
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

    public function setUp(): void
    {

        parent::setUp();

        $this->mockJob();
        $this->middleware = new HasRefreshTokenMiddleware();

    }

    /** @test */
    public function it_runs_with_refresh_token()
    {

        $this->job->shouldReceive('fire')->times(1);

        $this->job->refresh_token = factory(RefreshToken::class)->make();

        $this->middleware->handle($this->job, $this->next);
    }

    /** @test */
    public function it_fails_without_refresh_token()
    {

        $this->job->shouldReceive('fail')->times(1);

        $this->job->refresh_token = null;

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
