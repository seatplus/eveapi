<?php

namespace Seatplus\Eveapi\Jobs\Alliances;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

class AllianceInfoJob extends EsiJob
{
    public function __construct(public int $alliance_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['alliance', "alliance_id:{$this->alliance_id}", 'info'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        if ($this->batching() && $this->batch()->cancelled()) {
            return;
        }

        $response = $esi->alliance()->getAlliancesAllianceId($this->alliance_id);
        if ($response->isCachedLoad) {
            return;
        }

        AllianceInfo::firstOrNew(['alliance_id' => $this->alliance_id])->fill([
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
