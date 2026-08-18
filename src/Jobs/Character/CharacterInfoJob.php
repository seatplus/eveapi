<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Character\GetCharactersDetail;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

final class CharacterInfoJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersDetail::class;

    public function __construct(public int $characterId) {}

    #[\Override]
    public function tags(): array
    {
        return ['character', 'info', "character_id:{$this->characterId}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->characterId);
        if ($response->isCachedLoad) {
            return;
        }

        CharacterInfo::updateOrCreate(['character_id' => $this->characterId], [
            'name' => $response->name,
            'description' => $response->description,
            'birthday' => $response->birthday,
            'gender' => $response->gender,
            'race_id' => $response->race_id,
            'bloodline_id' => $response->bloodline_id,
            'security_status' => $response->security_status,
            'faction_id' => $response->faction_id,
            'title' => $response->corporation_title,
        ]);
    }
}
