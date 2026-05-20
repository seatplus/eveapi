<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Character\GetCharactersCharacterIdRoles;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;

final class CharacterRoleJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdRoles::class;

    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
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
        $response = self::OPERATION_CLASS::execute($esi, $this->character_id);
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
