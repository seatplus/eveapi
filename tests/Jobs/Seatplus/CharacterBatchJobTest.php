<?php

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterAssetsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterRolesHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\ContactHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\ContractHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\MailsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\SkillsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\WalletHydrateBatch;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\RefreshToken;

it('creates BatchUpdate entries', function () {
    Bus::fake();

    expect(testCharacter())->refresh_token->not()->toBeNull();

    (new CharacterBatchJob(testCharacter()->character_id))->handle();

    expect(\Seatplus\Eveapi\Models\BatchUpdate::all())->toHaveCount(RefreshToken::count())
        ->and(\Seatplus\Eveapi\Models\BatchUpdate::first())
        ->batchable_id->toBe(testCharacter()->character_id)
        ->batchable_type->toBe(\Seatplus\Eveapi\Models\Character\CharacterInfo::class)
        ->batchable->toBeInstanceOf(\Seatplus\Eveapi\Models\Character\CharacterInfo::class)
        ->finished_at->toBeNull()
        ->started_at->toBeInstanceOf(\Carbon\Carbon::class);
});

it('dispatches job', function (string $hydrateJob) {
    Bus::fake();

    (new CharacterBatchJob(testCharacter()->character_id))->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof $hydrateJob));
})->with([
    SkillsHydrateBatch::class,
    CorporationHistoryJob::class,
    CharacterRolesHydrateBatch::class,
]);

test('hydration test adds no jobs to batch if scopes are missing', function (string $hydrateBatchJob) {
    $refresh_token = Event::fakeFor(fn () => RefreshToken::factory()->scopes(['derp'])->create());

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(ContactHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $job->shouldNotReceive('batch');

    Bus::fake();

    $job->handle();
})->with([
    ContactHydrateBatch::class,
    CharacterRolesHydrateBatch::class,
    CharacterAssetsHydrateBatch::class,
    CharacterInfoJob::class,
]);

test('hydrationJob adds jobs to batch', function (array $scopes, string $hydrateBatchJob, array $addedJobs) {
    $refresh_token = Event::fakeFor(fn () => RefreshToken::factory()->scopes($scopes)->create());

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock($hydrateBatchJob . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with(buildAddedJobsArray($addedJobs, $job_container));

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
})->with([
    [['esi-contracts.read_character_contracts.v1'], ContractHydrateBatch::class, [CharacterContractsJob::class]],
    [
        ['esi-wallet.read_character_wallet.v1'],
        WalletHydrateBatch::class,
        [CharacterWalletJournalJob::class, CharacterWalletTransactionJob::class, CharacterBalanceJob::class],
    ],
    [
        ['esi-alliances.read_contacts.v1'],
        ContactHydrateBatch::class,
        [[AllianceContactJob::class, AllianceContactLabelJob::class]],
    ],
    [
        ['esi-corporations.read_contacts.v1'],
        ContactHydrateBatch::class,
        [[CorporationContactJob::class, CorporationContactLabelJob::class]],
    ],
    [
        ['esi-characters.read_contacts.v1'],
        ContactHydrateBatch::class,
        [[CharacterContactJob::class, CharacterContactLabelJob::class]],
    ],
    [
        ['esi-characters.read_corporation_roles.v1'],
        CharacterRolesHydrateBatch::class,
        [CharacterRoleJob::class],
    ],
    [
        ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1'],
        CharacterAssetsHydrateBatch::class,
        [[CharacterAssetJob::class, CharacterAssetsNameJob::class]],
    ],
    [
        ['esi-mail.read_mail.v1'],
        MailsHydrateBatch::class,
        [MailHeaderJob::class],
    ],
    [
        ['esi-skills.read_skillqueue.v1'],
        SkillsHydrateBatch::class,
        [SkillQueueJob::class],
    ],
    [
        ['esi-skills.read_skills.v1'],
        SkillsHydrateBatch::class,
        [SkillsJob::class],
    ],
]);

function buildAddedJobsArray(array $jobs, JobContainer $job_container) : array
{
    return collect($jobs)
        ->map(fn ($job) => is_array($job) ? buildAddedJobsArray($job, $job_container) : new $job($job_container))
        ->toArray();
}
