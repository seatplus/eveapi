<?php

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactResponse;

class CharacterContactJob extends ContactBaseJob
{
    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return $esi->contacts()->getCharactersCharacterIdContacts($this->character_id, $page);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'contacts'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactResponse($this->character_id, CharacterInfo::class), $esi);
    }
}
