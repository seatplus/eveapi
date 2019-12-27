<?php


namespace Seatplus\Eveapi\Actions\Location;


use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;

class ResolveUniverseStationByIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    const STATION_IDS_RANGE = [60000000, 64000000];

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/universe/stations/{station_id}/';

    /**
     * @var string
     */
    protected $version = 'v2';

    /**
     * @var array
     */
    private $path_values = [];

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function execute(int $location_id)
    {

        // If rate limited or not within ids range skip execution
        if($this->isEsiRateLimited() || ($location_id < head(self::STATION_IDS_RANGE) || $location_id > last(self::STATION_IDS_RANGE)))
            return;

        $this->setPathValues([
            'station_id' => $location_id
        ]);

        $result = $this->retrieve();

        $location = Location::firstOrCreate(['location_id' => $location_id]);

        Station::updateOrCreate([
            'station_id' => $location_id
        ], [
            'type_id'                    => $result->type_id,
            'name'                       => $result->name,
            'owner_id'                      => $result->owner ?? null,
            'race_id'                    => $result->race_id ?? null,
            'system_id'                  => $result->system_id,
            'reprocessing_efficiency'    => $result->reprocessing_efficiency,
            'reprocessing_stations_take' => $result->reprocessing_stations_take,
            'max_dockable_ship_volume'   => $result->max_dockable_ship_volume,
            'office_rental_cost'         => $result->office_rental_cost,
        ])->location()->save($location);

        $location->touch();

    }
}
