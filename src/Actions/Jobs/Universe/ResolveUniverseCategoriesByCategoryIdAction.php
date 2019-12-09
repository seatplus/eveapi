<?php

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\BaseActionJobAction;
use Seatplus\Eveapi\Actions\Jobs\HasPathValuesInterface;
use Seatplus\Eveapi\Models\Universe\Categories;
use Seatplus\Eveapi\Models\Universe\Groups;

class ResolveUniverseCategoriesByCategoryIdAction extends BaseActionJobAction implements HasPathValuesInterface
{
    /**
     * @var array
     */
    private $path_values;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $category_ids;


    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/categories/{category_id}/';
    }

    public function getVersion(): string
    {
        return 'v1';
    }

    public function execute(?int $category_id = null)
    {
        $this->category_ids = collect();

        if (! is_null($category_id))
            $this->category_ids->push($category_id);

        if(Cache::has('category_ids_to_resolve'))
        {
            $cached_group_ids = Cache::pull('category_ids_to_resolve');

            collect($cached_group_ids)->each(function ($cached_group_id) {
                $this->category_ids->push($cached_group_id);
            });
        }

        $this->category_ids->unique()->each(function ($category_id) {

            $this->setPathValues([
                'category_id' => $category_id
            ]);

            $response = $this->retrieve();

            if ($response->isCachedLoad()) return;

            return Categories::firstOrCreate(
                ['category_id' => $response->category_id],
                [
                    'name' => $response->name,
                    'published' => $response->published,
                ]
            );

        });

        // If execution was invoked with a specific type_id return the response
        if (! is_null($category_id))
            return Categories::find($category_id);

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
