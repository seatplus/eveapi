<?php

use Illuminate\Support\Facades\Http;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;

function buildTestSdeZip(): string
{
    $zipPath = tempnam(sys_get_temp_dir(), 'sde_test_').'.zip';

    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $categories = [
        json_encode(['_key' => 6, 'name' => ['en' => 'Ship'], 'published' => true]),
        json_encode(['_key' => 7, 'name' => ['en' => 'Module'], 'published' => false]),
    ];
    $zip->addFromString('categories.jsonl', implode("\n", $categories));

    $groups = [
        json_encode(['_key' => 25, 'categoryID' => 6, 'name' => ['en' => 'Frigate'], 'published' => true]),
    ];
    $zip->addFromString('groups.jsonl', implode("\n", $groups));

    $types = [
        json_encode(['_key' => 587, 'groupID' => 25, 'name' => ['en' => 'Rifter'], 'description' => ['en' => 'Fast Minmatar frigate.'], 'published' => true]),
    ];
    $zip->addFromString('types.jsonl', implode("\n", $types));

    $regions = [
        json_encode(['_key' => 10000002, 'name' => ['en' => 'The Forge'], 'description' => ['en' => 'Jita region.']]),
    ];
    $zip->addFromString('mapRegions.jsonl', implode("\n", $regions));

    $constellations = [
        json_encode(['_key' => 20000020, 'regionID' => 10000002, 'name' => ['en' => 'Kimotoro']]),
    ];
    $zip->addFromString('mapConstellations.jsonl', implode("\n", $constellations));

    $systems = [
        json_encode(['_key' => 30000142, 'constellationID' => 20000020, 'name' => ['en' => 'Jita'], 'securityClass' => 'B', 'securityStatus' => 0.9459]),
    ];
    $zip->addFromString('mapSolarSystems.jsonl', implode("\n", $systems));

    $zip->close();

    return $zipPath;
}

it('imports all SDE entities from a local zip', function () {
    $zipPath = buildTestSdeZip();

    try {
        $this->artisan('seatplus:sde-import', ['--source' => $zipPath])
            ->assertExitCode(0);
    } finally {
        @unlink($zipPath);
    }

    expect(Category::find(6))->not->toBeNull()
        ->and(Category::find(6)->name)->toBe('Ship')
        ->and(Category::find(6)->published)->toBeTrue()
        ->and(Category::find(7)->published)->toBeFalse();

    expect(Group::find(25))->not->toBeNull()
        ->and(Group::find(25)->name)->toBe('Frigate')
        ->and(Group::find(25)->category_id)->toBe(6);

    expect(Type::find(587))->not->toBeNull()
        ->and(Type::find(587)->name)->toBe('Rifter')
        ->and(Type::find(587)->group_id)->toBe(25);

    expect(Region::find(10000002))->not->toBeNull()
        ->and(Region::find(10000002)->name)->toBe('The Forge');

    expect(Constellation::find(20000020))->not->toBeNull()
        ->and(Constellation::find(20000020)->region_id)->toBe(10000002);

    expect(System::find(30000142))->not->toBeNull()
        ->and(System::find(30000142)->name)->toBe('Jita')
        ->and(System::find(30000142)->security_class)->toBe('B')
        ->and(System::find(30000142)->security_status)->toBe(0.9459)
        ->and(System::find(30000142)->constellation_id)->toBe(20000020);
});

it('upserts existing records on re-import', function () {
    $zipPath = buildTestSdeZip();

    try {
        $this->artisan('seatplus:sde-import', ['--source' => $zipPath]);
        $this->artisan('seatplus:sde-import', ['--source' => $zipPath])
            ->assertExitCode(0);
    } finally {
        @unlink($zipPath);
    }

    expect(Category::count())->toBe(2)
        ->and(Region::count())->toBe(1);
});

it('skips missing jsonl files with a warning', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'sde_empty_').'.zip';

    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('categories.jsonl', '');
    $zip->close();

    try {
        $this->artisan('seatplus:sde-import', ['--source' => $zipPath])
            ->assertExitCode(0);
    } finally {
        @unlink($zipPath);
    }

    expect(Category::count())->toBe(0)
        ->and(Group::count())->toBe(0);
});

it('downloads and imports SDE from CCP URL when no source given', function () {
    $zipPath = buildTestSdeZip();
    $zipBody = file_get_contents($zipPath);
    @unlink($zipPath);

    Http::fake([
        '*' => Http::response($zipBody, 200),
    ]);

    $this->artisan('seatplus:sde-import')
        ->assertExitCode(0);

    expect(Category::find(6))->not->toBeNull();
    expect(Region::find(10000002))->not->toBeNull();
});

it('fails when download returns an HTTP error', function () {
    Http::fake([
        '*' => Http::response('', 503),
    ]);

    $this->artisan('seatplus:sde-import')
        ->assertExitCode(1);
});
