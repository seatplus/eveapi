<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\BaseActionJobAction;
use Seatplus\Eveapi\Actions\Jobs\HasPathValuesInterface;
use Seatplus\Eveapi\Models\Universe\Groups;

class ResolveUniverseGroupsByGroupIdAction extends BaseActionJobAction implements HasPathValuesInterface
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

            return Groups::firstOrCreate(
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
            return Groups::find($group_id);

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
