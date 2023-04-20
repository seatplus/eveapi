<?php

use Illuminate\Support\Facades\Bus;
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
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\BatchStatistic;
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

it('contains public jobs in batch', function ($public_job) {
    Bus::fake();

    (new CharacterBatchJob(testCharacter()->character_id))->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof $public_job));
})->with([
    CharacterInfoJob::class,
    CorporationHistoryJob::class,
]);

it('contains jobs if refresh_token has scope', function (string $scope, array $classes) {
    updateRefreshTokenScopes($this->test_character->refresh_token, [$scope])->save();

    Bus::fake();

    $batch = new CharacterBatchJob(testCharacter()->character_id);

    //dd($batch->batch_jobs);

    // loop through classes and check if jobs that are instance of class are in batch
    foreach ($classes as $class) {
        // if class is of type array
        if (is_array($class)) {
            $jobs = collect($batch->getBatchJobs())->first(fn ($job) => is_array($job));
            $classes = $class;

            // expect lenght of jobs to be equal to classes
            expect($jobs)->toHaveCount(count($classes));

            if (count($jobs) !== count($classes)) {
                return false;
            }

            // loop through classes and check if jobs that are instance of class are in batch
            foreach ($classes as $class) {
                $collection = (collect($jobs)
                    ->map(fn ($job) => $job instanceof $class)
                    ->filter());

                expect($collection)->toHaveCount(1);
            }
        } else {
            $batched_jobs = collect($batch->getBatchJobs())->filter(fn ($job) => $job instanceof $class);
            expect($batched_jobs)->toHaveCount(1);
        }
    }
})->with([
    ['esi-assets.read_assets.v1', [[CharacterAssetJob::class, CharacterAssetsNameJob::class]]],
    ['esi-characters.read_corporation_roles.v1', [CharacterRoleJob::class]],
    ['esi-characters.read_contacts.v1', [[CharacterContactJob::class, CharacterContactLabelJob::class]]],
    ['esi-corporations.read_contacts.v1', [[CorporationContactJob::class, CorporationContactLabelJob::class]]],
    ['esi-alliances.read_contacts.v1', [[AllianceContactJob::class, AllianceContactLabelJob::class]]],
    ['esi-wallet.read_character_wallet.v1', [CharacterWalletJournalJob::class, CharacterWalletTransactionJob::class, CharacterBalanceJob::class]],
    ['esi-contracts.read_character_contracts.v1', [CharacterContractsJob::class]],
    ['esi-skills.read_skills.v1', [SkillsJob::class]],
    ['esi-skills.read_skillqueue.v1', [SkillQueueJob::class]],
    ['esi-mail.read_mail.v1', [MailHeaderJob::class]],
]);

it('Batch Statistics entry has been made', function () {
    Bus::fake();

    expect(BatchStatistic::count())->toBe(0);

    (new CharacterBatchJob(testCharacter()->character_id))->handle();

    expect(BatchStatistic::count())->toBe(1)
        ->and(BatchStatistic::first())->finished_at->toBeNull();
});
