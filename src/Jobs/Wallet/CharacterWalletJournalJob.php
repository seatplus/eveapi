<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Wallet\GetCharactersCharacterIdWalletJournal;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

final class CharacterWalletJournalJob extends WalletJournalBase
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdWalletJournal::class;

    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->character_id, $page);
    }

    #[\Override]
    protected function walletableId(): int
    {
        return $this->character_id;
    }

    #[\Override]
    protected function walletableType(): string
    {
        return CharacterInfo::class;
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'wallet', 'journal'];
    }
}
