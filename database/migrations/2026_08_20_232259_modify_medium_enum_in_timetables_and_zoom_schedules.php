<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE timetables MODIFY COLUMN medium ENUM('english', 'tamil', 'both') DEFAULT 'tamil'");
            DB::statement("ALTER TABLE zoom_schedules MODIFY COLUMN medium ENUM('english', 'tamil', 'both') DEFAULT 'tamil'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE timetables MODIFY COLUMN medium ENUM('english', 'tamil') DEFAULT 'tamil'");
            DB::statement("ALTER TABLE zoom_schedules MODIFY COLUMN medium ENUM('english', 'tamil') DEFAULT 'tamil'");
        }
    }
};
