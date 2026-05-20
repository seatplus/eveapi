<?php

namespace Seatplus\Eveapi\Jobs\Corporation;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Corporation\GetCorporationsCorporationId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class CorporationInfoJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationId::class;

    public function __construct(public int $corporation_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['corporation', 'info', "corporation_id:{$this->corporation_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = static::OPERATION_CLASS::execute($esi, $this->corporation_id);
        if ($response->isCachedLoad) {
            return;
        }

        CorporationInfo::firstOrNew(['corporation_id' => $this->corporation_id])->fill([
            'ticker' => $response->ticker,
            'name' => $response->name,
            'member_count' => $response->member_count,
            'ceo_id' => $response->ceo_id,
            'creator_id' => $response->creator_id,
            'tax_rate' => $response->tax_rate,
            'alliance_id' => $response->alliance_id,
            'date_founded' => $response->date_founded !== null ? carbon($response->date_founded) : null,
            'description' => $response->description,
            'faction_id' => $response->faction_id,
            'home_station_id' => $response->home_station_id,
            'shares' => $response->shares,
            'url' => $response->url,
            'war_eligible' => $response->war_eligible,
        ])->save();
    }
}
