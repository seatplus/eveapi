<?php

namespace Seatplus\Eveapi\Tests\Jobs\Character;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterRoleTest extends TestCase
{
    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

       $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token
        ]);

        CharacterRoleJob::dispatch($job_container)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterRoleJob::class);
    }

}
