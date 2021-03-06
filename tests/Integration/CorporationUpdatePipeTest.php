<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Hydrate\Corporation\CorporationMemberTrackingHydrateBatch;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationUpdatePipeTest extends TestCase
{
    /** @test */
    public function it_does_not_dispatches_corporation_member_tracking_as_non_director()
    {
        $this->test_character->refresh_token()->update(['scopes' => ['esi-corporations.track_members.v1']]);
        $this->test_character->roles()->update(['roles' => []]);

        $job_container = new JobContainer(['corporation_id' => $this->test_character->corporation->corporation_id]);

        $job = Mockery::mock(CorporationMemberTrackingHydrateBatch::class . '[batch]', [$job_container]);

        $job->shouldNotReceive('batch');

        Bus::fake();

        $job->handle();
    }

    /** @test */
    public function it_dispatches_member_tracking_hydration_job()
    {
        Bus::fake();

        (new UpdateCorporation)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof CorporationMemberTrackingHydrateBatch));

    }

    /** @test */
    public function hydration_job_dispatches_corporation_member_tracking_job_as_director()
    {

        $this->test_character->refresh_token()->update(['scopes' => ['esi-corporations.track_members.v1']]);
        $this->test_character->roles()->update(['roles' => ['Director']]);

        $job_container = new JobContainer(['corporation_id' => $this->test_character->corporation->corporation_id]);

        $job = Mockery::mock(CorporationMemberTrackingHydrateBatch::class . '[batch]', [$job_container]);

        $batch = Mockery::mock(Batch::class)->makePartial();

        $batch->shouldReceive('add')->once()->with([
            new CorporationMemberTrackingJob($job_container)
        ]);

        $job->shouldReceive('batch')
            ->once()->andReturn($batch);

        Bus::fake();

        $job->handle();

    }
}
