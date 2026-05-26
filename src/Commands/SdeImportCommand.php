<?php

namespace Seatplus\Eveapi\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;
use ZipArchive;

class SdeImportCommand extends Command
{
    protected $signature = 'seatplus:sde-import {--source= : Path to a local SDE zip file (skips download)}';

    protected $description = 'Import EVE Online static data (categories, groups, types, regions, constellations, systems) from the CCP SDE.';

    private const string SDE_URL = 'https://developers.eveonline.com/static-data/eve-online-static-data-latest-jsonl.zip';

    private const int CHUNK_SIZE = 500;

    /**
     * @var array<string, int>
     */
    private array $importedCounts = [];

    public function handle(): int
    {
        $source = $this->option('source');
        $ownedZip = $source === null;
        $tmpZip = $ownedZip ? sys_get_temp_dir().'/sde_'.str()->uuid().'.zip' : $source;
        $tmpDir = sys_get_temp_dir().'/sde_'.str()->uuid();

        try {
            if ($ownedZip) {
                $this->downloadSde($tmpZip);
            }

            $this->extractSde($tmpZip, $tmpDir);
            $this->importAll($tmpDir);
        } finally {
            if ($ownedZip) {
                @unlink($tmpZip);
            }

            $this->removeDirectory($tmpDir);
        }

        $this->newLine();
        $this->info('SDE import complete:');
        foreach ($this->importedCounts as $name => $count) {
            $this->line("  {$name}: {$count}");
        }

        return self::SUCCESS;
    }

    private function downloadSde(string $destination): void
    {
        $this->info('Downloading SDE…');

        $response = Http::timeout(300)->get(self::SDE_URL);

        if (! $response->successful()) {
            $this->fail("Download failed: HTTP {$response->status()}");
        }

        file_put_contents($destination, $response->body());
    }

    private function extractSde(string $zipPath, string $targetDir): void
    {
        $this->info('Extracting…');

        mkdir($targetDir, 0755, true);

        $zip = new ZipArchive;
        $result = $zip->open($zipPath);

        if ($result !== true) {
            $this->fail("Failed to open ZIP archive (code: {$result})");
        }

        $zip->extractTo($targetDir);
        $zip->close();
    }

    private function importAll(string $dir): void
    {
        $this->importCategories($dir);
        $this->importGroups($dir);
        $this->importTypes($dir);
        $this->importRegions($dir);
        $this->importConstellations($dir);
        $this->importSystems($dir);
    }

    private function importCategories(string $dir): void
    {
        $this->importJsonl(
            file: "{$dir}/categories.jsonl",
            label: 'Categories',
            table: (new Category)->getTable(),
            primaryKey: 'category_id',
            mapper: fn (array $row): array => [
                'category_id' => $row['_key'],
                'name' => $row['name']['en'] ?? null,
                'published' => $row['published'] ?? false,
            ],
            uniqueBy: ['category_id'],
        );
    }

    private function importGroups(string $dir): void
    {
        $this->importJsonl(
            file: "{$dir}/groups.jsonl",
            label: 'Groups',
            table: (new Group)->getTable(),
            primaryKey: 'group_id',
            mapper: fn (array $row): array => [
                'group_id' => $row['_key'],
                'category_id' => $row['categoryID'],
                'name' => $row['name']['en'] ?? null,
                'published' => $row['published'] ?? false,
            ],
            uniqueBy: ['group_id'],
        );
    }

    private function importTypes(string $dir): void
    {
        $this->importJsonl(
            file: "{$dir}/types.jsonl",
            label: 'Types',
            table: (new Type)->getTable(),
            primaryKey: 'type_id',
            mapper: fn (array $row): array => [
                'type_id' => $row['_key'],
                'group_id' => $row['groupID'],
                'name' => $row['name']['en'] ?? null,
                'description' => $row['description']['en'] ?? null,
                'published' => $row['published'] ?? false,
            ],
            uniqueBy: ['type_id'],
        );
    }

    private function importRegions(string $dir): void
    {
        $this->importJsonl(
            file: "{$dir}/mapRegions.jsonl",
            label: 'Regions',
            table: (new Region)->getTable(),
            primaryKey: 'region_id',
            mapper: fn (array $row): array => [
                'region_id' => $row['_key'],
                'name' => $row['name']['en'] ?? null,
                'description' => $row['description']['en'] ?? null,
            ],
            uniqueBy: ['region_id'],
        );
    }

    private function importConstellations(string $dir): void
    {
        $this->importJsonl(
            file: "{$dir}/mapConstellations.jsonl",
            label: 'Constellations',
            table: (new Constellation)->getTable(),
            primaryKey: 'constellation_id',
            mapper: fn (array $row): array => [
                'constellation_id' => $row['_key'],
                'region_id' => $row['regionID'],
                'name' => $row['name']['en'] ?? null,
            ],
            uniqueBy: ['constellation_id'],
        );
    }

    private function importSystems(string $dir): void
    {
        $this->importJsonl(
            file: "{$dir}/mapSolarSystems.jsonl",
            label: 'Systems',
            table: (new System)->getTable(),
            primaryKey: 'system_id',
            mapper: fn (array $row): array => [
                'system_id' => $row['_key'],
                'constellation_id' => $row['constellationID'],
                'name' => $row['name']['en'] ?? null,
                'security_class' => $row['securityClass'] ?? null,
                'security_status' => $row['securityStatus'] ?? 0.0,
            ],
            uniqueBy: ['system_id'],
        );
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $mapper
     * @param  string[]  $uniqueBy
     */
    private function importJsonl(
        string $label,
        string $file,
        string $table,
        string $primaryKey,
        callable $mapper,
        array $uniqueBy,
    ): void {
        if (! file_exists($file)) {
            $this->warn("  {$label}: file not found ({$file}), skipping.");

            return;
        }

        $this->info("Importing {$label}…");

        $handle = fopen($file, 'r');

        if ($handle === false) {
            $this->warn("  {$label}: cannot open file, skipping."); // @codeCoverageIgnore

            return; // @codeCoverageIgnore
        }

        $now = Carbon::now()->toDateTimeString();
        $chunk = [];
        $total = 0;

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $row = json_decode($line, true);

                if (! is_array($row)) {
                    continue;
                }

                $record = $mapper($row);
                $record['created_at'] = $now;
                $record['updated_at'] = $now;

                $chunk[] = $record;
                $total++;

                if (count($chunk) >= self::CHUNK_SIZE) {
                    DB::table($table)->upsert($chunk, $uniqueBy, array_diff(array_keys($chunk[0]), [$primaryKey]));
                    $chunk = [];
                    $progressBar->advance(self::CHUNK_SIZE);
                }
            }

            if ($chunk !== []) {
                DB::table($table)->upsert($chunk, $uniqueBy, array_diff(array_keys($chunk[0]), [$primaryKey]));
                $progressBar->advance(count($chunk));
            }
        } finally {
            fclose($handle);
        }

        $progressBar->finish();
        $this->newLine();

        $this->importedCounts[$label] = $total;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);

        if ($entries === false) {
            return; // @codeCoverageIgnore
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = "{$dir}/{$entry}";

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
