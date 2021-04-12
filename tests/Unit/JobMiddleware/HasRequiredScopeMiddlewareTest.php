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


    private $job;

    /**
     * @var \Closure
     */
    private $next;

    public function setUp(): void
    {

        parent::setUp();

        $this->middleware = new HasRequiredScopeMiddleware();
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


    private function mockJob(string $base_class)
    {

        $this->job = Mockery::mock($base_class);

        $this->next = function ($job) {
            $job->fire();
        };

    }

}
