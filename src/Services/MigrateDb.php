<?php

namespace Seatplus\Eveapi\Services;

use Doctrine\DBAL\Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateDb
{
    public function __construct()
    {
        $this->migrate();
    }

    /**
     * @throws \Throwable
     */
    public function migrate(): void
    {

        throw_unless($this->databaseExists('pgsql'), new \Exception('PostgreSql database does not exist'));

        if (! $this->databaseExists('mysql')) {
            return;
        }

        throw_unless($this->allPreviousMigrationsAreExecuted(), new \Exception('2025_01_02_185356_fix_killmail_items_table.php needs to be run on old mariadb database. If you do not wish to migrate data from mariadb to postgresql please remove mariadb container from docker-compose.yml'));

        $this->replicateTables();

    }

    public function databaseExists(string $connection): bool
    {
        try {
            DB::connection($connection)->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * @throws Exception
     */
    private function replicateTables(): void
    {
        $source_tables = Schema::connection('mysql')->getTableListing();

        DB::transaction(function () use ($source_tables) {
            foreach ($source_tables as $table) {

                if ($table === 'migrations') {
                    continue;
                }

                $this->replicateTable($table);
            }
        });

    }

    private function replicateTable(string $table): void
    {
        $first_column = Schema::connection('mysql')->getColumnListing($table)[0];

        DB::connection('mysql')->table($table)->orderBy($first_column)->chunk(1000, function (Collection $table_data) use ($table) {
            $table_data = $table_data
                ->map(fn (object $row) => (array) $row)
                ->map(fn (array $row) => array_filter($row, fn (string $key) => ! str_contains($key, 'name_normalized'), ARRAY_FILTER_USE_KEY));

            DB::connection('pgsql')->table($table)->insert($table_data->toArray());
        });
    }

    private function allPreviousMigrationsAreExecuted(): bool
    {

        $migrations = DB::connection('mysql')->table('migrations')->get();

        return $migrations->contains('migration', '2025_01_02_185356_fix_killmail_items_table');
    }
}
