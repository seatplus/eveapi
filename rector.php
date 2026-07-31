<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\Class_\TablePropertyToTableAttributeRector;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withSets([
        // SetList::DEAD_CODE,
        // SetList::EARLY_RETURN,
        // SetList::TYPE_DECLARATION,
        // SetList::CODE_QUALITY,
        // SetList::CODING_STYLE,
        LaravelLevelSetList::UP_TO_LARAVEL_130,
        LevelSetList::UP_TO_PHP_83,
        // Auto-adds @return generics to Eloquent relations
        // (AddGenericReturnTypeToRelationsRector) + whereHas closure type hints,
        // so Rector's (Larastan-less) reflection can resolve model access —
        // the tool-native alternative to hand-written @property docblocks.
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
        // Pest coding-style modernizations for the test suite.
        PestSetList::CODING_STYLE,
    ])
    ->withSkip([
        // Larastan does not yet read #[Table] for table-name / primary-key inference;
        // skip until Larastan support is added upstream.
        TablePropertyToTableAttributeRector::class,
    ])
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/database',
    ]);
