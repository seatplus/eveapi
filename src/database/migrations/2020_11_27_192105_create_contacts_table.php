<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->morphs('contactable');
            $table->unsignedBigInteger('contact_id');
            $table->enum('contact_type', ['character', 'corporation', 'alliance', 'faction']);
            $table->boolean('is_blocked')->nullable();
            $table->boolean('is_watched')->nullable();
            $table->float('standing',10,2);
            $table->timestamps();
        });

        Schema::create('contact_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->unsignedBigInteger('label_id');
            $table->timestamps();
        });

        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->morphs('labelable');
            $table->unsignedBigInteger('label_id');
            $table->text('label_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('labels');
        Schema::dropIfExists('contact_labels');
        Schema::dropIfExists('contacts');
    }
}
