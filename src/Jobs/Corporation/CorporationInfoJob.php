<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Corporation;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Corporation\GetCorporationsCorporationId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

final class CorporationInfoJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationId::class;

    public function __construct(public int $corporationId) {}

    #[\Override]
    public function tags(): array
    {
        return ['corporation', 'info', "corporation_id:{$this->corporationId}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->corporationId);
        if ($response->isCachedLoad) {
            return;
        }

        CorporationInfo::firstOrNew(['corporation_id' => $this->corporationId])->fill([
            'ticker' => $response->ticker,
            'name' => $response->name,
            'member_count' => $response->member_count,
            'ceo_id' => $response->ceo_id,
            'creator_id' => $response->creator_id,
            // ESI replaced the scalar tax_rate with tax_rates{isk,loyalty_point} AND changed the
            // unit: tax_rates.isk is documented "ISK tax rate (0.0% - 100.0%)" (example: 10), where
            // the old tax_rate was a 0.0-1.0 fraction. Divide to keep this column's established
            // meaning, so existing rows and new ones remain directly comparable.
            'tax_rate' => $response->tax_rates->isk / 100,
            'alliance_id' => $response->alliance_id,
            'date_founded' => $response->date_founded !== null ? carbon($response->date_founded) : null,
            'description' => $response->description,
            // faction_id was the faction that OWNED an NPC corporation; enlisted_faction_id is the
            // faction a PLAYER corporation is enlisted with in factional warfare. Same column, a
            // different concept — renaming it (and clearing the now-wrong values) is tracked separately.
            'faction_id' => $response->enlisted_faction_id,
            'home_station_id' => $response->home_station_id,
            'shares' => $response->shares,
            'url' => $response->url,
            'war_eligible' => $response->war_eligible,
        ])->save();
    }
}
