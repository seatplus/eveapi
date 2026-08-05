<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Alliances;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Alliance\GetAlliancesAllianceId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

final class AllianceInfoJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetAlliancesAllianceId::class;

    public function __construct(public int $allianceId) {}

    #[\Override]
    public function tags(): array
    {
        return ['alliance', "alliance_id:{$this->allianceId}", 'info'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        // batching() is already false for a cancelled batch (it is
        // `$batch && ! finished() && ! cancelled()`), so it must not gate this check —
        // `batching() && batch()->cancelled()` can never be true.
        if ($this->batch()?->cancelled()) {
            return;
        }

        $response = self::OPERATION_CLASS::execute($esi, $this->allianceId);
        if ($response->isCachedLoad) {
            return;
        }

        AllianceInfo::firstOrNew(['alliance_id' => $this->allianceId])->fill([
            'creator_corporation_id' => $response->creator_corporation_id,
            'creator_id' => $response->creator_id,
            'date_founded' => carbon($response->date_founded),
            'executor_corporation_id' => $response->executor_corporation_id,
            'faction_id' => $response->faction_id,
            'name' => $response->name,
            'ticker' => $response->ticker,
        ])->save();
    }
}
