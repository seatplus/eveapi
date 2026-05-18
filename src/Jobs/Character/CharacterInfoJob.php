<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class CharacterInfoJob extends EsiJob
{
    public function __construct(public int $character_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['character', 'info', "character_id:{$this->character_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->characters()->getCharactersCharacterId($this->character_id);
        if ($response->isCachedLoad) {
            return;
        }

        CharacterInfo::updateOrCreate(['character_id' => $this->character_id], [
            'name' => $response->name,
            'description' => $response->description,
            'birthday' => $response->birthday,
            'gender' => $response->gender,
            'race_id' => $response->race_id,
            'bloodline_id' => $response->bloodline_id,
            'security_status' => $response->security_status,
            'faction_id' => $response->faction_id,
            'title' => $response->title,
        ]);
    }
}
