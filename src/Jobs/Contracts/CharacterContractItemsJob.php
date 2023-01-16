<?php

namespace Seatplus\Eveapi\Jobs\Contracts;

class CharacterContractItemsJob extends ContractItemsJob
{
    public function __construct(
        public int $character_id,
        public int $contract_id,
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/characters/{character_id}/contracts/{contract_id}/items/',
            version: 'v1',
        );

        $this->setPathValues([
            'character_id' => $character_id,
            'contract_id' => $contract_id,
        ]);

        $this->setRequiredScope(head(config('eveapi.scopes.character.contracts')));
    }

    public function tags(): array
    {
        return [
            'character',
            'contract',
            'items',
            'character_id:' . $this->character_id,
            'contract_id:' . $this->contract_id,
        ];
    }
}
{

}