<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\Constellation;

class ResolveUniverseConstellationByConstellationIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    private $path_values;

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/constellations/{constellation_id}/';
    }

    public function getVersion(): string
    {
        return 'v1';
    }

    /**
     * @param mixed $path_values
     */
    public function setPathValues(array $path_values): void
    {

        $this->path_values = $path_values;
    }

    /**
     * @return mixed
     */
    public function getPathValues(): array
    {

        return $this->path_values;
    }

    public function execute(int $constellation_id)
    {

        $this->setPathValues([
            'constellation_id' => $constellation_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) return;

        return Constellation::firstOrCreate(
            ['constellation_id' => $response->constellation_id],
            [
                'region_id' => $response->region_id,
                'name' => $response->name,
            ]
        );

    }
}
