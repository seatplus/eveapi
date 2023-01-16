<?php


use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Hydrate\Corporation\CorporationDivisionHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Corporation\CorporationMemberTrackingHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Corporation\CorporationWalletHydrateBatch;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;


test('it dispatches jobs if token with role, scope and permission is present', function (string $role, string $scope, array $job_classes) {
    Bus::fake();

    updateRefreshTokenScopes($this->test_character->refresh_token, [$scope])->save();
    $this->test_character->roles()->updateOrCreate(['roles' => ['Director']]);

    (new UpdateCorporation)->handle();

    // loop through classes and check if jobs that are instance of class are in batch
    foreach ($job_classes as $job_class) {

        // if class is of type array
        if (is_array($job_class)) {

            Bus::assertBatched(function (\Illuminate\Bus\PendingBatch $batch) use ($job_class) {

                // get array inside the jobs array
                $jobs = $batch->jobs->first(fn($job) => is_array($job));
                $classes = $job_class;

                // expect lenght of jobs to be equal to classes
                if (count($jobs) !== count($classes)) {
                    return false;
                }

                // loop through classes and check if jobs that are instance of class are in batch
                foreach ($classes as $job_class) {
                    if (!collect($jobs)->first(fn($job) => $job instanceof $job_class)) {
                        return false;
                    }
                }

                return true;

            });


        } else {
            Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof $job_class));
        }
    }


})->with([
    ['Director', 'esi-corporations.read_divisions.v1', [CorporationDivisionsJob::class]],
    ['Director', 'esi-corporations.track_members.v1', [CorporationMemberTrackingJob::class]],
    ['Accountant', 'esi-wallet.read_corporation_wallets.v1', [[CorporationBalanceJob::class, CorporationWalletJournalJob::class]]],
    ['Junior_Accountant', 'esi-wallet.read_corporation_wallets.v1', [[CorporationBalanceJob::class, CorporationWalletJournalJob::class]]],
]);





