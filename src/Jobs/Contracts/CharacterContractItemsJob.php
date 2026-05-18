<?php

namespace Seatplus\Eveapi\Jobs\Contracts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterContractItemsJob extends ContractItemsJob
{
    public function __construct(
        public int $character_id,
        public int $contract_id,
    ) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function fetchItems(EsiClient $esi): EsiResult
    {
        return $esi->contracts()->getCharactersCharacterIdContractsContractIdItems(
            $this->character_id,
            $this->contract_id
        );
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', 'contract', 'items', "character_id:{$this->character_id}", "contract_id:{$this->contract_id}"];
    }
}
