<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // The top-level item (direct child of the root Location) an asset ultimately sits in.
            // Lets the assets view resolve "which top-level items match/contain a matching asset at
            // any depth" as a single flat, indexed `SELECT DISTINCT root_item_id WHERE
            // root_location_id = ? AND <filter>` — no tree eager-load, no PHP recursion.
            $table->bigInteger('root_item_id')->nullable()->index();
        });

        $this->backfillRootItemIds();
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('root_item_id'); // drops its index too
        });
    }

    /**
     * Set root_item_id for every existing asset. A top-level asset (its location_id is not another
     * of the same owner's item_ids, i.e. it points at a real Location) is its own root item;
     * children (location_id == parent item_id) inherit the root item's item_id down the chain.
     * Scoped by owner so item_id / location_id ranges cannot collide across characters/corporations.
     */
    private function backfillRootItemIds(): void
    {
        DB::statement(<<<'SQL'
            WITH RECURSIVE tree AS (
                SELECT a.item_id, a.assetable_id, a.assetable_type, a.item_id AS root_item_id
                FROM assets a
                WHERE NOT EXISTS (
                    SELECT 1 FROM assets p
                    WHERE p.item_id = a.location_id
                      AND p.assetable_id = a.assetable_id
                      AND p.assetable_type = a.assetable_type
                )
                UNION ALL
                SELECT c.item_id, c.assetable_id, c.assetable_type, t.root_item_id
                FROM assets c
                JOIN tree t
                  ON c.location_id = t.item_id
                 AND c.assetable_id = t.assetable_id
                 AND c.assetable_type = t.assetable_type
            )
            UPDATE assets SET root_item_id = tree.root_item_id
            FROM tree
            WHERE assets.item_id = tree.item_id
        SQL);
    }
};
