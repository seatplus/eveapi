<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Wallet;

use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Wallet\GetCorporationsCorporationIdWallets;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

final class CorporationBalanceJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationIdWallets::class;

    public function __construct(public int $corporationId) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        $token = (new FindCorporationRefreshToken)(
            $this->corporationId,
            head(config('eveapi.scopes.corporation.wallet')),
            ['Accountant', 'Junior_Accountant']
        );
        throw_unless($token, new \Exception("No eligible refresh token found for corporation {$this->corporationId}"));

        return $token;
    }

    #[\Override]
    public function tags(): array
    {
        return ['corporation', "corporation_id:{$this->corporationId}", 'balances'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->corporationId);
        if ($response->isCachedLoad) {
            return;
        }

        $balances = collect($response->data)->map(fn (object $wallet) => [
            'balanceable_id' => $this->corporationId,
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
            CorporationWalletJournalByDivisionJob::dispatch($this->corporationId, $balance['division'])->onQueue('high');
            CorporationWalletTransactionByDivisionJob::dispatch($this->corporationId, $balance['division'])->onQueue('high');
        });
    }
}
