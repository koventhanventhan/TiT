<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'zoom_schedules',
            'payments',
            'assignments',
            'learning_materials',
            'attendances',
            'site_settings',
            'admin_messages'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('institute_id')->nullable()->after('id')->constrained('institutes');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'zoom_schedules',
            'payments',
            'assignments',
            'learning_materials',
            'attendances',
            'site_settings',
            'admin_messages'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('institute_id');
            });
        }
    }
};
