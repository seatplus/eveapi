<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Wallet\GetCharactersCharacterIdWallet;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\Balance;

final class CharacterBalanceJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdWallet::class;

    public function __construct(public int $characterId) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->characterId}", 'wallet', 'balance'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->characterId);
        if ($response->isCachedLoad) {
            return;
        }

        Balance::updateOrCreate(
            ['balanceable_id' => $this->characterId, 'balanceable_type' => CharacterInfo::class],
            ['balance' => $response->data]
        );
    }
}
