<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterRoleJob extends EsiJob
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
        return ['character', "character_id:{$this->character_id}", 'roles'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->characters()->getCharactersCharacterIdRoles($this->character_id);
        if ($response->isCachedLoad) {
            return;
        }

        CharacterRole::updateOrCreate(['character_id' => $this->character_id], [
            'roles' => $response->roles,
            'roles_at_base' => $response->roles_at_base,
            'roles_at_hq' => $response->roles_at_hq,
            'roles_at_other' => $response->roles_at_other,
        ]);
    }
}
