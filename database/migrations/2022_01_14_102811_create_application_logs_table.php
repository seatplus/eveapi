<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Recruitment\ApplicationLogs;

return new class extends Migration
{
    public function up()
    {
        Schema::create('application_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications');
            $table->morphs('causer');
            $table->enum('type', ['comment', 'decision'])->index();
            $table->longText('comment');
            $table->timestamps();
        });

        Application::query()->whereNotNull('causer_type')
            ->get()
            ->each(fn (Application $application) => ApplicationLogs::create([
                'application_id' => $application->id,
                'causer_type' => data_get($application, 'causer_type'),
                'causer_id' => data_get($application, 'causer_id'),
                'type' => 'decision',
                'comment' => data_get($application, 'comment'),
            ]));

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('causer_type');
            $table->dropColumn('causer_id');
            $table->dropColumn('comment');
        });
    }
};
