<?php

namespace Seatplus\Eveapi\Tests\Jobs\Character;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterInfoTest extends TestCase
{
    /** @test */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        CharacterInfo::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterInfo::class);
    }

}
