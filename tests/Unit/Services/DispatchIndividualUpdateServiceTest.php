<?php


namespace Seatplus\Eveapi\Tests\Unit\Services;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Services\DispatchIndividualUpdate;
use Seatplus\Eveapi\Tests\TestCase;

class DispatchIndividualUpdateServiceTest extends TestCase
{
    /** @test */
    public function it_dispatches_job()
    {
        $refresh_token = $this->test_character->refresh_token;
        $job = 'character.assets';

        Queue::fake();

        (new DispatchIndividualUpdate($refresh_token))->execute($job);

        $job_class = config('eveapi.jobs')[$job];

        Queue::assertPushedOn('high', $job_class);
    }
}
