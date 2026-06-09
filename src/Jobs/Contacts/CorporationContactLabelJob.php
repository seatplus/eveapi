<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\EsiSchema\Resources\Contacts\GetCorporationsCorporationIdContactsLabels;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactLabelsResponse;

final class CorporationContactLabelJob extends ContactBaseJob
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationIdContactsLabels::class;

    public function __construct(
        public int $corporationId,
        public int $characterId
    ) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    protected function fetchPage(EsiClient $esi, int $page): EsiResult
    {
        return self::OPERATION_CLASS::execute($esi, $this->corporationId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['corporation', "corporation_id:{$this->corporationId}", 'contacts', 'label'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $this->handleProcessor(new ProcessContactLabelsResponse($this->corporationId, CorporationInfo::class), $esi);
    }
}
