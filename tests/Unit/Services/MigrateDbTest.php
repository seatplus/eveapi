<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Services\MigrateDb;

beforeEach(function () {

    Event::fake();
});

describe('with migration run', function () {

    beforeEach(function () {

        // verify no tables exist
        $tables = Schema::connection('mysql')->getTableListing();

        expect($tables)->toBeArray()
            ->and($tables)->toBeEmpty();

        // create migration table
        Schema::connection('mysql')->create('migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });

        // insert 2025_01_02_185356_fix_killmail_items_table into migration table
        DB::connection('mysql')->table('migrations')->insert([
            'migration' => '2025_01_02_185356_fix_killmail_items_table',
            'batch' => 1,
        ]);
    });

    afterEach(function () {

        // drop all tables
        $tables = Schema::connection('mysql')->getTableListing();

        foreach ($tables as $table) {
            Schema::connection('mysql')->drop($table);
        }

        // Reset the database connection
        DB::purge('mysql');
    });

    it('migrates assets', function () {
        // Arrange

        Schema::connection('mysql')->create('assets', function ($table) {
            $table->bigInteger('item_id')->primary();
            $table->morphs('assetable');
            $table->boolean('is_blueprint_copy')->default(false);
            $table->boolean('is_singleton')->default(false);
            // location_flag
            $table->string('location_flag')->nullable();
            // location_id
            $table->bigInteger('location_id')->nullable();
            // location_type enum (station, solar_system, item, other)
            $table->enum('location_type', ['station', 'solar_system', 'item', 'other'])->nullable();
            // quantity
            $table->integer('quantity');
            // type_id
            $table->integer('type_id');
            $table->string('name')->nullable();
            // name normalized
            $table->string('name_normalized')->virtualAs("regexp_replace(name, '[^A-Za-z0-9]', '')")->index();

            // type first
            $table->string('type_name_normalized')->index()->nullable();

            // group second
            $table->integer('group_id')->nullable();
            $table->string('group_name_normalized')->index()->nullable();

            // category third
            $table->integer('category_id')->nullable();
            $table->string('category_name_normalized')->index()->nullable();

            $table->timestamps();
        });

        Asset::factory()->connection('mysql')->withName()->count(10)->create();

        expect(Asset::on('mysql')->count())->toBe(10)
            ->and(Asset::count())->toBe(0);

        // Act

        new MigrateDb;

        // Assert

        expect(Asset::count())->toBe(10);
    });

});

it('has no existing mariadb database', function () {
    // Arrange

    $mock = mock(MigrateDb::class, function (MockInterface $mock) {
        $mock->shouldReceive('databaseExists')
            ->with('mysql')
            ->once()
            ->andReturnFalse();
    })->makePartial();

    // Act
    $mock->migrate();

    // Assert
    expect(true)->toBeTrue();
});

it('returns false if db connection fails', function () {
    // Arrange
    DB::shouldReceive()
        ->connection('pgsql')
        ->andThrow(new Exception('PostgreSql database does not exist'));

    $mock = mock(MigrateDb::class, function (MockInterface $mock) {
        $mock->shouldReceive('migrate')
            ->andReturnNull();
    })->makePartial();

    // Act
    $result = $mock->databaseExists('pgsql');

    // Assert
    expect($result)->toBeFalse();
});
