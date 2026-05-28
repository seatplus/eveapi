<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contracts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contracts\GetCharactersCharacterIdContractsContractIdItems;
use Seatplus\Eveapi\Models\RefreshToken;

final class CharacterContractItemsJob extends ContractItemsBase
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdContractsContractIdItems::class;

    public function __construct(
        public int $character_id,
        public int $contract_id,
    ) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function fetchItems(EsiClient $esi): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->character_id, $this->contract_id);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', 'contract', 'items', "character_id:{$this->character_id}", "contract_id:{$this->contract_id}"];
    }
}
