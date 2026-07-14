<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string> the *_name_normalized columns searched with case-insensitive ILIKE
     */
    private array $trigramColumns = [
        'type_name_normalized',
        'group_name_normalized',
        'category_name_normalized',
    ];

    public function up(): void
    {
        // group_id / category_id back the filterByGroupIds/filterByCategoryIds scopes but were
        // never indexed.
        Schema::table('assets', function (Blueprint $table) {
            $table->index('group_id');
            $table->index('category_id');
        });

        // The *_name_normalized columns only have plain B-tree indexes, which Postgres cannot use
        // for case-insensitive ILIKE search. pg_trgm GIN indexes accelerate ILIKE prefix/contains.
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach ($this->trigramColumns as $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS assets_{$column}_trgm ON assets USING gin ({$column} gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        foreach ($this->trigramColumns as $column) {
            DB::statement("DROP INDEX IF EXISTS assets_{$column}_trgm");
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropIndex(['category_id']);
        });
    }
};
