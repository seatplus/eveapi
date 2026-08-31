<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contacts\GetAlliancesAllianceIdContacts;
use Seatplus\EsiSchema\Responses\AlliancesAllianceIdContactsGetItem;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactResponse;

final class AllianceContactJob extends ContactBaseJob
{
    protected const string OPERATION_CLASS = GetAlliancesAllianceIdContacts::class;

    public function __construct(
        public int $allianceId,
        public int $characterId
    ) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    /** @return EsiResult<array<AlliancesAllianceIdContactsGetItem>> */
    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->allianceId, $page);
    }

    #[\Override]
    public function tags(): array
    {
        return ['alliance', "alliance_id:{$this->allianceId}", 'contacts'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactResponse($this->allianceId, AllianceInfo::class), $esi);
    }
}
