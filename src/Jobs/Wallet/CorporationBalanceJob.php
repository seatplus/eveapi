<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Wallet\GetCorporationsCorporationIdWallets;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class CorporationBalanceJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationIdWallets::class;

    public function __construct(public int $corporation_id) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        $token = (new FindCorporationRefreshToken)(
            $this->corporation_id,
            head(config('eveapi.scopes.corporation.wallet')),
            ['Accountant', 'Junior_Accountant']
        );
        throw_unless($token, new \Exception("No eligible refresh token found for corporation {$this->corporation_id}"));

        return $token;
    }

    #[\Override]
    public function tags(): array
    {
        return ['corporation', "corporation_id:{$this->corporation_id}", 'balances'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = GetCorporationsCorporationIdWallets::execute($esi, $this->corporation_id);
        if ($response->isCachedLoad) {
            return;
        }

        $balances = collect($response->data)->map(fn (object $wallet) => [
            'balanceable_id' => $this->corporation_id,
            'balanceable_type' => CorporationInfo::class,
            'division' => $wallet->division,
            'balance' => $wallet->balance,
        ]);

        Balance::upsert($balances->toArray(), ['balanceable_id', 'balanceable_type', 'division'], ['balance']);

        $this->dispatchDivisionJobs($balances);
    }

    private function dispatchDivisionJobs(Collection $balances): void
    {
        $balances->each(function (array $balance) {
            CorporationWalletJournalByDivisionJob::dispatch($this->corporation_id, $balance['division'])->onQueue('high');
            CorporationWalletTransactionByDivisionJob::dispatch($this->corporation_id, $balance['division'])->onQueue('high');
        });
    }
}
