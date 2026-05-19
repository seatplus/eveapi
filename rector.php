<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
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
    ])
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/database',
    ]);
// uncomment to reach your current PHP version
// ->withPhpSets()
