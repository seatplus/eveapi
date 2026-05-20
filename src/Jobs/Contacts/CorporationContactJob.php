<?php

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contacts\GetCorporationsCorporationIdContacts;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactResponse;

class CorporationContactJob extends ContactBaseJob
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationIdContacts::class;

    public function __construct(
        public int $corporation_id,
        public int $character_id
    ) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return static::OPERATION_CLASS::execute($esi, $this->corporation_id, $page);
    }

    #[\Override]
    public function tags(): array
    {
        return ['corporation', "corporation_id:{$this->corporation_id}", 'contacts'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactResponse($this->corporation_id, CorporationInfo::class), $esi);
    }
}
