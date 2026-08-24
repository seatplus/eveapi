<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Wallet\GetCharactersCharacterIdWalletTransactions;
use Seatplus\EsiSchema\Responses\CharactersCharacterIdWalletTransactionsGetItem;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

final class CharacterWalletTransactionJob extends WalletTransactionBase
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdWalletTransactions::class;

    public function __construct(public int $characterId) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    /** @return EsiResult<array<CharactersCharacterIdWalletTransactionsGetItem>> */
    #[\Override]
    protected function fetchTransactions(EsiClient $esi, ?int $fromId): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->characterId, $fromId);
    }

    #[\Override]
    protected function transactionableId(): int
    {
        return $this->characterId;
    }

    #[\Override]
    protected function transactionableType(): string
    {
        return CharacterInfo::class;
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->characterId}", 'wallet', 'transaction'];
    }
}
