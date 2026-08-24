<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contracts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contracts\GetCharactersCharacterIdContractsContractIdItems;
use Seatplus\EsiSchema\Responses\CharactersCharacterIdContractsContractIdItemsGetItem;
use Seatplus\Eveapi\Models\RefreshToken;

final class CharacterContractItemsJob extends ContractItemsBase
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdContractsContractIdItems::class;

    public function __construct(
        public int $characterId,
        public int $contractId,
    ) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    /** @return EsiResult<array<CharactersCharacterIdContractsContractIdItemsGetItem>> */
    #[\Override]
    protected function fetchItems(EsiClient $esi): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->characterId, $this->contractId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', 'contract', 'items', "character_id:{$this->characterId}", "contract_id:{$this->contractId}"];
    }
}
