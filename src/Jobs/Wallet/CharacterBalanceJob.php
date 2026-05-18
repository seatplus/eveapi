<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\Balance;

class CharacterBalanceJob extends EsiJob
{
    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'wallet', 'balance'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->wallet()->getCharactersCharacterIdWallet($this->character_id);
        if ($response->isCachedLoad) {
            return;
        }

        Balance::updateOrCreate(
            ['balanceable_id' => $this->character_id, 'balanceable_type' => CharacterInfo::class],
            ['balance' => $response->data]
        );
    }
}
