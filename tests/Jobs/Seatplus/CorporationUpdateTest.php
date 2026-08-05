<?php

use Illuminate\Bus\PendingBatch;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;
use Seatplus\Eveapi\Models\BatchStatistic;

test('it dispatches jobs if token with role, scope and permission is present', function (
    string $role,
    string $scope,
    array $jobClasses,
    ?int $corporationId
) {
    Bus::fake();

    updateRefreshTokenScopes($this->test_character->refreshToken, [$scope])->save();
    $this->test_character->roles()->update(['roles' => ['Director']]);

    new UpdateCorporation($corporationId)->handle();

    // loop through classes and check if jobs that are instance of class are in batch
    foreach ($jobClasses as $jobClass) {
        // if class is of type array
        if (is_array($jobClass)) {
            Bus::assertBatched(function (PendingBatch $batch) use ($jobClass) {
                // get array inside the jobs array
                $jobs = $batch->jobs->first(fn ($job) => is_array($job));
                $classes = $jobClass;

                // expect lenght of jobs to be equal to classes
                if (count($jobs) !== count($classes)) {
                    return false;
                }

                return array_all($classes, fn ($jobClass) => collect($jobs)->first(fn ($job) => $job instanceof $jobClass));
            });
        } else {
            Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof $jobClass));
        }
    }
})->with([
    ['Director', 'esi-corporations.read_divisions.v1', [CorporationDivisionsJob::class]],
    ['Director', 'esi-corporations.track_members.v1', [CorporationMemberTrackingJob::class]],
    ['Accountant', 'esi-wallet.read_corporation_wallets.v1', [[CorporationBalanceJob::class, CorporationWalletJournalJob::class]]],
    ['Junior_Accountant', 'esi-wallet.read_corporation_wallets.v1', [[CorporationBalanceJob::class, CorporationWalletJournalJob::class]]],
])->with([
    null, fn () => testCharacter()->corporation_id,
]);

it('Batch Statistics entry has been made', function () {
    Bus::fake();

    expect(BatchStatistic::count())->toBe(0);

    (new UpdateCorporation)->handle();

    expect(BatchStatistic::count())->toBe(1)
        ->and(BatchStatistic::first())->finished_at->toBeNull();
});

it('has middleware', function () {
    expect((new UpdateCorporation)->middleware())->toBeArray();
});

it('is unique per corporation so duplicate dispatches collapse', function () {
    expect(new UpdateCorporation(90000001))->toBeInstanceOf(ShouldBeUnique::class)
        ->and(new UpdateCorporation(90000001)->uniqueId())->toBe('90000001')
        ->and(new UpdateCorporation(90000002)->uniqueId())->toBe('90000002')
        ->and((new UpdateCorporation)->uniqueId())->toBe('all');
});
