<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\Class_\TablePropertyToTableAttributeRector;
use RectorLaravel\Set\LaravelLevelSetList;

return RectorConfig::configure()
    ->withSets([
        // SetList::DEAD_CODE,
        // SetList::EARLY_RETURN,
        // SetList::TYPE_DECLARATION,
        // SetList::CODE_QUALITY,
        // SetList::CODING_STYLE,
        LaravelLevelSetList::UP_TO_LARAVEL_130,
        LevelSetList::UP_TO_PHP_83,
        PestSetList::CODING_STYLE,
    ])
    ->withSkip([
        // Larastan does not yet read #[Table] for table-name / primary-key inference;
        // skip until Larastan support is added upstream.
        TablePropertyToTableAttributeRector::class,
        // Rector cannot reflect dynamic Eloquent model properties (e.g. $group_id
        // on a Collection|Model|null), so it errors on these model-heavy tests.
        // Skip them so the Pest coding-style rules still run on the rest.
        __DIR__.'/tests/Integration/CategoryLifecycleTest.php',
        __DIR__.'/tests/Integration/GroupLifecycleTest.php',
        __DIR__.'/tests/Integration/TypeLifeCycleTest.php',
        __DIR__.'/tests/Jobs/Contacts/ContactJobTest.php',
        __DIR__.'/tests/Unit/Models/ApplicationsModelTest.php',
        __DIR__.'/tests/Unit/Models/ContractModelTest.php',
        __DIR__.'/tests/Unit/Models/RefreshTokenModelTest.php',
        __DIR__.'/tests/Unit/Services/UpdateRefreshTokenServiceTest.php',
        __DIR__.'/tests/Unit/Services/ResolveLocation/StructureRefreshTokenFinderTest.php',
        __DIR__.'/tests/Jobs/Assets/EnrichAssetTypeGroupCategoryJobTest.php',
    ])
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/database',
    ]);
