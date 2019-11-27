<?php

namespace Seatplus\Eveapi\Tests\Jobs\Character;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAssetTest extends TestCase
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

        CharacterAssetJob::dispatch($job_container)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterAssetJob::class);
    }

}
