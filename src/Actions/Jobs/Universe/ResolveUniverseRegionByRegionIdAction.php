<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\Region;

class ResolveUniverseRegionByRegionIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    private $path_values;

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/regions/{region_id}/';
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

    public function execute(int $region_id)
    {

        $this->setPathValues([
            'region_id' => $region_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) return;

        return Region::firstOrCreate(
            ['region_id' => $region_id],
            [
                'name' => $response->name,
                'description' => $response->optional('description'),
            ]
        );

    }
}
