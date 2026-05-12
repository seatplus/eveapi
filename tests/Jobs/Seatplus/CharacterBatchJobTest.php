<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Assets\EnrichAssetTypeGroupCategoryJob;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
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
use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

it('creates BatchUpdate entries', function () {
    Bus::fake();

    expect(testCharacter())->refresh_token->not()->toBeNull();

    (new CharacterBatchJob(testCharacter()->character_id))->handle();

    expect(BatchUpdate::all())->toHaveCount(RefreshToken::count())
        ->and(BatchUpdate::first())
        ->batchable_id->toBe(testCharacter()->character_id)
        ->batchable_type->toBe(CharacterInfo::class)
        ->batchable->toBeInstanceOf(CharacterInfo::class)
        ->finished_at->toBeNull()
        ->started_at->toBeInstanceOf(Carbon::class);
});

it('contains public jobs in batch', function ($public_job) {
    Bus::fake();

    (new CharacterBatchJob(testCharacter()->character_id))->handle();

    Bus::assertBatched(fn ($batch) => isInstanceOfClassInArray($batch->jobs, $public_job));
})->with([
    CharacterInfoJob::class,
    CharacterAffiliationJob::class,
    CorporationHistoryJob::class,
]);

it('contains jobs if refresh_token has scope', function (string $scope, array $classes) {
    Queue::fake();
    updateRefreshTokenScopes($this->test_character->refresh_token, [$scope])->save();

    Bus::fake();

    $batch = new CharacterBatchJob(testCharacter()->character_id);
    $jobs = $batch->getBatchJobs();

    // loop through classes and check if jobs that are instance of class are in batch
    foreach ($classes as $class) {
        expect(isInstanceOfClassInArray($jobs, $class))->toBeTrue();
    }
})->with([
    ['esi-assets.read_assets.v1', [CharacterAssetJob::class, CharacterAssetsNameJob::class, EnrichAssetTypeGroupCategoryJob::class]],
    ['esi-characters.read_corporation_roles.v1', [CharacterRoleJob::class]],
    ['esi-characters.read_contacts.v1', [CharacterContactJob::class, CharacterContactLabelJob::class]],
    ['esi-corporations.read_contacts.v1', [CorporationContactJob::class, CorporationContactLabelJob::class]],
    ['esi-alliances.read_contacts.v1', [AllianceContactJob::class, AllianceContactLabelJob::class]],
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

function isInstanceOfClassInArray($jobs, $class): bool
{
    foreach ($jobs as $job) {
        if (is_array($job)) {
            // Recursively check the sub-array
            if (isInstanceOfClassInArray($job, $class)) {
                return true;
            }
        } elseif ($job instanceof $class) {
            return true;
        }
    }

    return false;
}
