<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseCategoriesCategoryId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Category;

final class ResolveUniverseCategoryByIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseCategoriesCategoryId::class;

    public function __construct(private readonly int $categoryId) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'category', "category_id:{$this->categoryId}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->categoryId);

        if ($response->isCachedLoad) {
            return;
        }

        Category::firstOrCreate(
            ['category_id' => $response->category_id],
            ['name' => $response->name, 'published' => $response->published]
        );
    }
}
