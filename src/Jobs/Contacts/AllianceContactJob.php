<?php

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contacts\GetAlliancesAllianceIdContacts;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactResponse;

class AllianceContactJob extends ContactBaseJob
{
    protected const string OPERATION_CLASS = GetAlliancesAllianceIdContacts::class;

    public function __construct(
        public int $alliance_id,
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
        return static::OPERATION_CLASS::execute($esi, $this->alliance_id, $page);
    }

    #[\Override]
    public function tags(): array
    {
        return ['alliance', "alliance_id:{$this->alliance_id}", 'contacts'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactResponse($this->alliance_id, AllianceInfo::class), $esi);
    }
}
