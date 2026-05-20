<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Wallet\GetCharactersCharacterIdWalletTransactions;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterWalletTransactionJob extends WalletTransactionBase
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdWalletTransactions::class;

    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function fetchTransactions(EsiClient $esi, ?int $fromId): EsiResult
    {
        return static::OPERATION_CLASS::execute($esi, $this->character_id, $fromId);
    }

    #[\Override]
    protected function transactionableId(): int
    {
        return $this->character_id;
    }

    #[\Override]
    protected function transactionableType(): string
    {
        return CharacterInfo::class;
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'wallet', 'transaction'];
    }
}
