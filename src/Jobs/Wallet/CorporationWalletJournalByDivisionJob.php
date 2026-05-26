<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Wallet\GetCorporationsCorporationIdWalletsDivisionJournal;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

final class CorporationWalletJournalByDivisionJob extends WalletJournalBase
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationIdWalletsDivisionJournal::class;

    public function __construct(
        public int $corporation_id,
        private readonly int $division
    ) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        $token = (new FindCorporationRefreshToken)(
            $this->corporation_id,
            head(config('eveapi.scopes.corporation.wallet')),
            ['Accountant', 'Junior_Accountant']
        );
        throw_unless($token, new \Exception("No eligible refresh token for corporation {$this->corporation_id}"));

        return $token;
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->corporation_id, $this->division, $page);
    }

    #[\Override]
    protected function walletableId(): int
    {
        return $this->corporation_id;
    }

    #[\Override]
    protected function walletableType(): string
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
        return ['corporation', "corporation_id:{$this->corporation_id}", 'wallet', 'journal', "division:{$this->division}"];
    }
}
