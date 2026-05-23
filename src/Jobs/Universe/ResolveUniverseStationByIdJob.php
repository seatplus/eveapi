<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseStationsStationId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;

final class ResolveUniverseStationByIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseStationsStationId::class;

    public const array STATION_IDS_RANGE = [60000000, 64000000];

    public function __construct(public int $location_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'station', "location_id:{$this->location_id}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        if ($this->location_id < head(self::STATION_IDS_RANGE) || $this->location_id > last(self::STATION_IDS_RANGE)) {
            return;
        }

        $response = self::OPERATION_CLASS::execute($esi, $this->location_id);

        Station::updateOrCreate(['station_id' => $this->location_id], [
            'type_id' => $response->type_id,
            'name' => $response->name,
            'owner_id' => $response->owner ?? null,
            'race_id' => $response->race_id ?? null,
            'system_id' => $response->system_id,
            'reprocessing_efficiency' => $response->reprocessing_efficiency,
            'reprocessing_stations_take' => $response->reprocessing_stations_take,
            'max_dockable_ship_volume' => $response->max_dockable_ship_volume,
            'office_rental_cost' => $response->office_rental_cost,
        ])->touch();

        Location::updateOrCreate(['location_id' => $this->location_id], [
            'locatable_id' => $this->location_id,
            'locatable_type' => Station::class,
        ]);
    }
}
