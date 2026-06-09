<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contacts\GetCharactersCharacterIdContacts;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactResponse;

final class CharacterContactJob extends ContactBaseJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdContacts::class;

    public function __construct(public int $characterId) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->characterId, $page);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->characterId}", 'contacts'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactResponse($this->characterId, CharacterInfo::class), $esi);
    }
}
