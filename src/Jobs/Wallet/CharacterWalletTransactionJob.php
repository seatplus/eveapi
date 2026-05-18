<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterWalletTransactionJob extends WalletTransactionBase
{
    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function fetchTransactions(EsiClient $esi, ?int $fromId): EsiResult
    {
        return $esi->wallet()->getCharactersCharacterIdWalletTransactions($this->character_id, $fromId);
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
