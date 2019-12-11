<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\Types;

class ResolveUniverseTypesByTypeIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    /**
     * @var array
     */
    private $path_values;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $type_ids;

    /**
     * @param mixed $request_body
     */
    public function setRequestBody(array $request_body): void
    {

        $this->request_body = $request_body;
    }

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/types/{type_id}/';
    }

    public function getVersion(): string
    {
        return 'v3';
    }

    public function execute(?int $type_id = null)
    {
        $this->type_ids = (new AddAndGetIdsFromCache('type_ids_to_resolve', $type_id))->execute();

        $this->type_ids->unique()->each(function ($type_id) {

            $this->setPathValues([
                'type_id' => $type_id,
            ]);

            $response = $this->retrieve();

            if ($response->isCachedLoad()) return;

            return Types::firstOrCreate(
                ['type_id' => $response->type_id],
                [
                    'group_id' => $response->group_id,
                    'name' => $response->name,
                    'description' => $response->description,
                    'published' => $response->published,

                    'capacity' => $response->optional('capacity'),
                    'graphic_id' => $response->optional('graphic_id'),
                    'icon_id' => $response->optional('icon_id'),
                    'market_group_id' => $response->optional('market_group_id'),
                    'mass' => $response->optional('mass'),
                    'packaged_volume' => $response->optional('packaged_volume'),
                    'portion_size' => $response->optional('portion_size'),
                    'radius' => $response->optional('radius'),
                    'volume' => $response->optional('volume'),
                ]
            );

        });

        // If execution was invoked with a specific type_id return the response
        if (! is_null($type_id))
            return Types::find($type_id);

        return null;

    }

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }
}
