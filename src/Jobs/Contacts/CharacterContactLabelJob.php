<?php

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

    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->character_id);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'contacts', 'labels'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactLabelsResponse($this->character_id, CharacterInfo::class), $esi);
    }
}
