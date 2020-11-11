<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToSsoScopesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sso_scopes', function (Blueprint $table) {
            $table->string('morphable_id')->nullable()->change();
            $table->string('morphable_type')->nullable()->change();
            $table->enum('type', ['default', 'user', 'global'])->default('default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sso_scopes', function (Blueprint $table) {
            $table->string('morphable_id')->change();
            $table->string('morphable_type')->change();
            $table->dropColumn('type');
        });
    }
}
