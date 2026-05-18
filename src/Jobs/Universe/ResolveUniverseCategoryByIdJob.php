<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Category;

class ResolveUniverseCategoryByIdJob extends EsiJob
{
    public function __construct(private int $category_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'category', "category_id:{$this->category_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->universe()->getUniverseCategoriesCategoryId($this->category_id);

        Category::firstOrCreate(
            ['category_id' => $response->category_id],
            ['name' => $response->name, 'published' => $response->published]
        );
    }
}
