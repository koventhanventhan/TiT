<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zoom_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('timetable_id')->nullable()->after('zoom_account_id');
            $table->index('timetable_id');
        });
    }

    public function down(): void
    {
        Schema::table('zoom_schedules', function (Blueprint $table) {
            $table->dropIndex(['timetable_id']);
            $table->dropColumn('timetable_id');
        });
    }
};
