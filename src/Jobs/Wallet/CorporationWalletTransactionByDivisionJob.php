<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Wallet\GetCorporationsCorporationIdWalletsDivisionTransactions;
use Seatplus\EsiSchema\Responses\CorporationsCorporationIdWalletsDivisionTransactionsGetItem;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

final class CorporationWalletTransactionByDivisionJob extends WalletTransactionBase
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationIdWalletsDivisionTransactions::class;

    public function __construct(
        public int $corporationId,
        private readonly int $division
    ) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        $token = (new FindCorporationRefreshToken)(
            $this->corporationId,
            head(config('eveapi.scopes.corporation.wallet')),
            ['Accountant', 'Junior_Accountant']
        );
        throw_unless($token, new \Exception("No eligible refresh token for corporation {$this->corporationId}"));

        return $token;
    }

    /** @return EsiResult<array<CorporationsCorporationIdWalletsDivisionTransactionsGetItem>> */
    #[\Override]
    protected function fetchTransactions(EsiClient $esi, ?int $fromId): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->corporationId, $this->division, $fromId);
    }

    #[\Override]
    protected function transactionableId(): int
    {
        return $this->corporationId;
    }

    #[\Override]
    protected function transactionableType(): string
    {
        return CorporationInfo::class;
    }

    #[\Override]
    protected function division(): int
    {
        return $this->division;
    }

    #[\Override]
    public function tags(): array
    {
        return ['corporation', "corporation_id:{$this->corporationId}", 'wallet', 'transaction', "division:{$this->division}"];
    }
}
