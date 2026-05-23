<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseCategoriesCategoryId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Category;

final class ResolveUniverseCategoryByIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseCategoriesCategoryId::class;

    public function __construct(private readonly int $category_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'category', "category_id:{$this->category_id}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->category_id);

        Category::firstOrCreate(
            ['category_id' => $response->category_id],
            ['name' => $response->name, 'published' => $response->published]
        );
    }
}
