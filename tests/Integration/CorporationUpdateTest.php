<?php


use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Hydrate\Corporation\CorporationMemberTrackingHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Corporation\CorporationWalletHydrateBatch;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;

it('does not dispatches corporation member tracking as non director', function () {
    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.track_members.v1'])->save();
    $this->test_character->roles()->update(['roles' => []]);

    $job_container = new JobContainer(['corporation_id' => $this->test_character->corporation->corporation_id]);

    $job = Mockery::mock(CorporationMemberTrackingHydrateBatch::class . '[batch]', [$job_container]);

    $job->shouldNotReceive('batch');

    Bus::fake();

    $job->handle();
});

it('dispatches member tracking hydration job', function () {
    Bus::fake();

    (new UpdateCorporation)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof CorporationMemberTrackingHydrateBatch));
});

test('hydration job dispatches corporation member tracking job as director', function () {
    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.track_members.v1'])->save();
    $this->test_character->roles()->update(['roles' => ['Director']]);

    $job_container = new JobContainer(['corporation_id' => $this->test_character->corporation->corporation_id]);

    $job = Mockery::mock(CorporationMemberTrackingHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        new CorporationMemberTrackingJob($job_container),
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

it('dispatches wallet hydration job', function () {
    Bus::fake();

    (new UpdateCorporation)->handle();

    Bus::assertBatched(
        fn ($batch) => $batch
        ->jobs
        ->first(fn ($job) => $job instanceof CorporationWalletHydrateBatch)
    );
});

test('hydration job dispatches corporation wallet job as accountant', function () {
    \Illuminate\Support\Facades\Event::fakeFor(fn () => updateRefreshTokenScopes($this->test_character->refresh_token, config('eveapi.scopes.corporation.wallet'))->save());

    $this->test_character->roles()->update(['roles' => ['Accountant']]);

    $job_container = new JobContainer(['corporation_id' => $this->test_character->corporation->corporation_id]);

    $job = Mockery::mock(CorporationWalletHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        new CorporationDivisionsJob($job_container),
        [
            new CorporationBalanceJob($job_container),
            new CorporationWalletJournalJob($job_container),
        ],
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});
