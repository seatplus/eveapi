<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Scope;
use Seatplus\Eveapi\Models\FiltersByLocationWatchList;
use Seatplus\Eveapi\Models\FiltersByTypeWatchList;
use Seatplus\Eveapi\Models\LocationWatchListInterface;
use Seatplus\Eveapi\Models\TypeWatchListInterface;

/**
 * Every concrete model under src/Models that implements $interface.
 *
 * Discovered rather than hardcoded so a model added later is covered without
 * anyone remembering to extend this test.
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
 * The watchlist filter contract cannot live on the marker interfaces: interface
 * methods must be public, and Larastan resolves `#[Scope]` methods on the query
 * builder only when they are non-public, so a public declaration makes the scopes
 * unresolvable in every consuming package.
 *
 * The paired trait's `abstract protected` declarations get PHP to enforce that the
 * methods exist with the right signature. What PHP cannot enforce is that they stay
 * non-public and keep `#[Scope]` — which is precisely what broke seatplus/web — nor
 * that a model implementing the interface remembers to use the trait at all. Those
 * three gaps are what this test closes.
 */
it('enforces the watchlist filter contract on every implementor', function (string $interface, string $trait) {
    $implementors = watchListImplementors($interface);

    // An empty set would let every assertion below pass vacuously, so the scan
    // failing has to be a test failure rather than a silent green.
    expect($implementors)->not->toBeEmpty();

    $required = array_map(
        fn (ReflectionMethod $method): string => $method->getName(),
        (new ReflectionClass($trait))->getMethods(ReflectionMethod::IS_ABSTRACT)
    );

    expect($required)->not->toBeEmpty();

    foreach ($implementors as $model) {
        expect(class_uses_recursive($model))->toContain($trait);

        foreach ($required as $method) {
            $reflection = new ReflectionMethod($model, $method);

            expect($reflection->isPublic())->toBeFalse()
                ->and($reflection->getAttributes(Scope::class))->not->toBeEmpty();
        }
    }
})->with([
    [TypeWatchListInterface::class, FiltersByTypeWatchList::class],
    [LocationWatchListInterface::class, FiltersByLocationWatchList::class],
]);
