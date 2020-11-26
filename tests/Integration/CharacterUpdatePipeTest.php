<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;
use Mockery;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob as CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterAssetsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterRolesHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Corporation\CorporationMemberTrackingHydrateBatch;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterUpdatePipeTest extends TestCase
{

    /** @test */
    public function it_dispatches_character_info()
    {
        Bus::fake();

        (new UpdateCharacter)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof CharacterInfoJob));
    }

    /** @test */
    public function it_dispatch_assets_hydration_job()
    {
        Bus::fake();

        (new UpdateCharacter)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof CharacterAssetsHydrateBatch));
    }

    /** @test */
    public function it_does_not_dispatch_asset_job_for_missing_scopes()
    {
        Bus::fake();

        (new UpdateCharacter)->handle();

        $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);

        $job = Mockery::mock(CharacterAssetsHydrateBatch::class . '[batch]', [$job_container]);

        $job->shouldNotReceive('batch');

        Bus::fake();

        $job->handle();

        //Bus::assertNotDispatched(CharacterAssetJob::class);
    }

    /** @test */
    public function assets_hydration_job_dispatches_character_assets_job()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1']
            ]);
        });

        $job_container = new JobContainer(['refresh_token' => $refresh_token]);

        $job = Mockery::mock(CharacterAssetsHydrateBatch::class . '[batch]', [$job_container]);

        $batch = Mockery::mock(Batch::class)->makePartial();

        $batch->shouldReceive('add')->once()->with([
            [
                new CharacterAssetJob($job_container),
                new CharacterAssetsNameJob($job_container)
            ]
        ]);

        $job->shouldReceive('batch')
            ->once()->andReturn($batch);

        Bus::fake();

        $job->handle();
    }

    /** @test */
    public function if_constructor_receives_single_refresh_token_push_update_to_high_queue()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1']
            ]);
        });

        Bus::fake();

        (new UpdateCharacter($refresh_token))->handle();

        Bus::assertBatched(fn(PendingBatchFake $batch) => $batch->queue() === 'high');
    }

    /** @test */
    public function it_dispatches_character_role_job()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-characters.read_corporation_roles.v1']
            ]);
        });

        Bus::fake();

        (new UpdateCharacter($refresh_token))->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof CharacterRolesHydrateBatch));
    }

    /** @test */
    public function roles_hydration_job_dispatches_character_roles_job()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-characters.read_corporation_roles.v1']
            ]);
        });

        $job_container = new JobContainer(['refresh_token' => $refresh_token]);

        $job = Mockery::mock(CharacterRolesHydrateBatch::class . '[batch]', [$job_container]);

        $batch = Mockery::mock(Batch::class)->makePartial();

        $batch->shouldReceive('add')->once()->with([
            new CharacterRoleJob($job_container),
        ]);

        $job->shouldReceive('batch')
            ->once()->andReturn($batch);

        Bus::fake();

        $job->handle();
    }

    /** @test */
    public function hydration_does_not_dispatch_role_job_for_missing_scopes()
    {
        Bus::fake();

        (new UpdateCharacter)->handle();

        $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);

        $job = Mockery::mock(CharacterRolesHydrateBatch::class . '[batch]', [$job_container]);

        $job->shouldNotReceive('batch');

        Bus::fake();

        $job->handle();

        //Bus::assertNotDispatched(CharacterAssetJob::class);
    }

}
