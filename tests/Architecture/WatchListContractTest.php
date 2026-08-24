<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Scope;
use Seatplus\Eveapi\Models\LocationWatchListInterface;
use Seatplus\Eveapi\Models\TypeWatchListInterface;

/**
 * Every concrete model under src/Models that implements $interface.
 *
 * Discovered rather than hardcoded so a model added later is covered without anyone
 * remembering to extend this test — which is the whole point of the contract.
 *
 * @return array<int, class-string>
 */
function watchListImplementors(string $interface): array
{
    $sourceRoot = dirname(__DIR__, 2).'/src';

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceRoot.'/Models', FilesystemIterator::SKIP_DOTS)
    );

    $implementors = [];

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $class = 'Seatplus\\Eveapi\\'.str_replace(
            '/',
            '\\',
            substr($file->getPathname(), strlen($sourceRoot) + 1, -strlen('.php'))
        );

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if (! $reflection->isAbstract() && $reflection->implementsInterface($interface)) {
            $implementors[] = $class;
        }
    }

    sort($implementors);

    return $implementors;
}

/**
 * The watchlist filter contract, enforced here because it cannot be enforced by PHP.
 *
 * It cannot live on the marker interfaces: interface methods must be public, and
 * Larastan resolves `#[Scope]` methods on the query builder only when they are
 * non-public, so a public declaration makes the scopes unresolvable in every
 * consuming package. See ARCHITECTURE.md, Decision 10.
 *
 * Three things are asserted per implementor — the method exists, it is non-public,
 * and it carries `#[Scope]`. The last two are what consumers actually depend on:
 * widening `protected` to `public` is legal inheritance PHP will never complain
 * about, and it is exactly what broke seatplus/web.
 */
it('enforces the watchlist filter contract on every implementor', function (string $interface, array $required) {
    $implementors = watchListImplementors($interface);

    // An empty set would let every assertion below pass vacuously, so a broken scan
    // has to fail the test rather than report a silent green.
    expect($implementors)->not->toBeEmpty();

    $violations = [];

    foreach ($implementors as $model) {
        foreach ($required as $method) {
            if (! method_exists($model, $method)) {
                $violations[] = "{$model}::{$method}() is missing";

                continue;
            }

            $reflection = new ReflectionMethod($model, $method);

            if ($reflection->isPublic()) {
                $violations[] = "{$model}::{$method}() must not be public — Larastan will not resolve it on the builder";
            }

            if ($reflection->getAttributes(Scope::class) === []) {
                $violations[] = "{$model}::{$method}() is missing the #[Scope] attribute";
            }
        }
    }

    expect($violations)->toBe([]);
})->with([
    [TypeWatchListInterface::class, ['filterByTypeIds', 'filterByGroupIds', 'filterByCategoryIds']],
    [LocationWatchListInterface::class, ['filterByRegionIds', 'filterBySystemIds']],
]);
