<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\ClassMethod\ScopeNamedClassMethodToScopeAttributedClassMethodRector;
use RectorLaravel\Set\LaravelLevelSetList;

return RectorConfig::configure()
    ->withSets([
        // SetList::DEAD_CODE,
        // SetList::EARLY_RETURN,
        // SetList::TYPE_DECLARATION,
        // SetList::CODE_QUALITY,
        // SetList::CODING_STYLE,

        // …WITHOUT_ATTRIBUTES drops laravel130-attributes.php, i.e. the whole
        // property-to-PHP-attribute migration (#[Table], #[Fillable], #[Signature],
        // #[Description], …). We have not adopted Laravel 13 attributes yet: Larastan
        // does not read #[Table] for table-name / primary-key inference, and the console
        // attributes are a consumer-visible change of their own. Opting out by set says
        // that once, instead of naming each rule in withSkip() below.
        LaravelLevelSetList::UP_TO_LARAVEL_130_WITHOUT_ATTRIBUTES,

        // Single-version PHP sets, deliberately NOT LevelSetList::UP_TO_PHP_85 — that one
        // is cumulative (php85 + up-to-php84 + … down to 7.x), so on a tree that had never
        // been Rector-clean it also applied long-pending 8.0/8.1/8.3 rewrites that have
        // nothing to do with the PHP floor moving to 8.5. At least one of them was not
        // behaviour preserving here (arrow-function-to-first-class-callable where the
        // closure is later rebound via Closure::call()). Scoping to 8.4 + 8.5 keeps this
        // config to the version bump it is named for. Adopt older levels deliberately if
        // ever wanted.
        SetList::PHP_84,
        SetList::PHP_85,
    ])
    ->withSkip([
        // Lives in laravel120, so the …WITHOUT_ATTRIBUTES set above does not cover it.
        // Turns `public function scopeNeedsUniverseEnrichment(Builder $q)` into a
        // `protected` #[Scope]-attributed method — consumer-visible, and dependent on the
        // same Larastan gap as #[Table]. Adopt deliberately, not as a side effect.
        ScopeNamedClassMethodToScopeAttributedClassMethodRector::class,

        // #[\Override] on properties is an optional assertion, not something 8.5 requires,
        // and here it would land on framework-declared properties ($table, $primaryKey,
        // $model, $with, $dispatchesEvents) rather than on anything this package owns.
        // That is 86 attributes of noise for a typo guard, and it couples class loading to
        // laravel/framework's internals: the rule does no vendor-boundary check (it decides
        // purely by PHPStan reflection over parents, and is not configurable), so if a
        // future 13.x restructures one of those properties the result is a fatal at class
        // load rather than a deprecation. The existing 236 #[\Override] on *methods* stay —
        // those are house style and assert against methods, not framework property bags.
        AddOverrideAttributeToOverriddenPropertiesRector::class,
    ])
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/database',
    ]);
