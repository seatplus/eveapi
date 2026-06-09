<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contacts\GetAlliancesAllianceIdContactsLabels;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactLabelsResponse;

final class AllianceContactLabelJob extends ContactBaseJob
{
    protected const string OPERATION_CLASS = GetAlliancesAllianceIdContactsLabels::class;

    public function __construct(
        public int $allianceId,
        public int $characterId,
    ) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->allianceId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['alliance', "alliance_id:{$this->allianceId}", 'contacts', 'labels'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactLabelsResponse($this->allianceId, AllianceInfo::class), $esi);
    }
}
