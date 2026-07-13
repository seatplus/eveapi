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
            // The top-level location an asset ultimately sits in. Lets the assets view filter
            // "location contains a matching asset at any depth" as a single flat, indexed
            // whereHas instead of a 3-level whereHas + PHP tree recursion.
            // (location_id is already indexed via assets_location_id_index.)
            $table->bigInteger('root_location_id')->nullable()->index();
        });

        $this->backfillRootLocationIds();
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('root_location_id'); // drops its index too
        });
    }

    /**
     * Set root_location_id for every existing asset. Roots are assets whose location_id is not
     * another of the same owner's item_ids (i.e. it points at a real Location); children
     * (location_id == parent item_id) inherit the root's location_id. Scoped by owner so
     * item_id / location_id ranges cannot collide across characters/corporations.
     */
    private function backfillRootLocationIds(): void
    {
        DB::statement(<<<'SQL'
            WITH RECURSIVE tree AS (
                SELECT a.item_id, a.assetable_id, a.assetable_type, a.location_id AS root_location_id
                FROM assets a
                WHERE NOT EXISTS (
                    SELECT 1 FROM assets p
                    WHERE p.item_id = a.location_id
                      AND p.assetable_id = a.assetable_id
                      AND p.assetable_type = a.assetable_type
                )
                UNION ALL
                SELECT c.item_id, c.assetable_id, c.assetable_type, t.root_location_id
                FROM assets c
                JOIN tree t
                  ON c.location_id = t.item_id
                 AND c.assetable_id = t.assetable_id
                 AND c.assetable_type = t.assetable_type
            )
            UPDATE assets SET root_location_id = tree.root_location_id
            FROM tree
            WHERE assets.item_id = tree.item_id
        SQL);
    }
};
