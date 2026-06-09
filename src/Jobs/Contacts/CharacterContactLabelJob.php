<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contacts\GetCharactersCharacterIdContactsLabels;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactLabelsResponse;

final class CharacterContactLabelJob extends ContactBaseJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdContactsLabels::class;

    public function __construct(public int $characterId) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->characterId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->characterId}", 'contacts', 'labels'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactLabelsResponse($this->characterId, CharacterInfo::class), $esi);
    }
}
