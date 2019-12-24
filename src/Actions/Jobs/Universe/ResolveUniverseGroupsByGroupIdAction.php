<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Actions\Seatplus\AddAndGetIdsFromCache;

class ResolveUniverseGroupsByGroupIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    /**
     * @var array
     */
    private $path_values;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $group_ids;

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/groups/{group_id}/';
    }

    public function getVersion(): string
    {
        return 'v1';
    }

    public function execute(?int $group_id = null)
    {
        $this->group_ids = (new AddAndGetIdsFromCache('group_ids_to_resolve', $group_id))->execute();

        $this->group_ids->unique()->each(function ($group_id) {

            $this->setPathValues([
                'group_id' => $group_id,
            ]);

            $response = $this->retrieve();

            if ($response->isCachedLoad()) return;

            return Group::firstOrCreate(
                ['group_id' => $response->group_id],
                [
                    'category_id' => $response->category_id,
                    'name' => $response->name,
                    'published' => $response->published,
                ]
            );

        });

        // If execution was invoked with a specific type_id return the response
        if (! is_null($group_id))
            return Group::find($group_id);

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
