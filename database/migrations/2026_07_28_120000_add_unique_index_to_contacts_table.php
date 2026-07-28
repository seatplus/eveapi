<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The composite key (contact_id, contactable_id, contactable_type) has always been treated
        // as unique by ProcessContactResponse's updateOrCreate, but was never enforced. It is now
        // the conflict target of a bulk Contact::upsert(), which requires a matching unique index.
        $this->removeDuplicateContacts();

        Schema::table('contacts', function (Blueprint $table) {
            $table->unique(['contact_id', 'contactable_id', 'contactable_type'], 'contacts_composite_unique');
        });
    }

    /**
     * Defensively collapse any legacy duplicate rows (kept lowest id) so the unique index can be
     * created. updateOrCreate already guaranteed uniqueness, so this normally matches nothing;
     * contact_labels of removed rows fall away via their ON DELETE CASCADE foreign key.
     */
    private function removeDuplicateContacts(): void
    {
        DB::statement(<<<'SQL'
            DELETE FROM contacts a
            USING contacts b
            WHERE a.id > b.id
              AND a.contact_id = b.contact_id
              AND a.contactable_id = b.contactable_id
              AND a.contactable_type = b.contactable_type
        SQL);
    }
};
