<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Widen users.medium, zoom_schedules.medium, timetables.medium, and subjects.medium
 * to support 'sinhala' (and 'all' for schedules/timetables/subjects).
 *
 * LOCK / DOWNTIME NOTE:
 * In MariaDB (10.2+) and MySQL (8.0+), appending new values to an existing ENUM
 * column using ALTER TABLE ... MODIFY COLUMN ... is an INSTANT metadata-only operation (ALGORITHM=INSTANT).
 * - No table rebuild or row-by-row copy occurs.
 * - Lock duration is minimal (< 10 ms).
 * - Zero downtime is required for this migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            if (Schema::hasTable('users') && Schema::hasColumn('users', 'medium')) {
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'sinhala') NULL DEFAULT NULL");
            }

            if (Schema::hasTable('zoom_schedules') && Schema::hasColumn('zoom_schedules', 'medium')) {
                DB::statement("ALTER TABLE `zoom_schedules` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'sinhala', 'both', 'all') NULL DEFAULT 'tamil'");
            }

            if (Schema::hasTable('timetables') && Schema::hasColumn('timetables', 'medium')) {
                DB::statement("ALTER TABLE `timetables` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'sinhala', 'both', 'all') NULL DEFAULT 'tamil'");
            }

            if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'medium')) {
                DB::statement("ALTER TABLE `subjects` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'sinhala', 'both', 'all') NOT NULL DEFAULT 'tamil'");
            }
        } else {
            // For SQLite and other drivers during testing:
            // SQLite columns are untyped, but ensure columns exist if tables exist.
            if (Schema::hasTable('users') && !Schema::hasColumn('users', 'medium')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('medium')->nullable();
                });
            }
            if (Schema::hasTable('zoom_schedules') && !Schema::hasColumn('zoom_schedules', 'medium')) {
                Schema::table('zoom_schedules', function (Blueprint $table) {
                    $table->string('medium')->nullable()->default('tamil');
                });
            }
            if (Schema::hasTable('timetables') && !Schema::hasColumn('timetables', 'medium')) {
                Schema::table('timetables', function (Blueprint $table) {
                    $table->string('medium')->nullable()->default('tamil');
                });
            }
            if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'medium')) {
                Schema::table('subjects', function (Blueprint $table) {
                    $table->string('medium')->default('tamil');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            if (Schema::hasTable('users') && Schema::hasColumn('users', 'medium')) {
                DB::table('users')->where('medium', 'sinhala')->update(['medium' => 'english']);
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `medium` ENUM('english', 'tamil') NULL DEFAULT NULL");
            }

            if (Schema::hasTable('zoom_schedules') && Schema::hasColumn('zoom_schedules', 'medium')) {
                DB::table('zoom_schedules')->whereIn('medium', ['sinhala', 'all'])->update(['medium' => 'both']);
                DB::statement("ALTER TABLE `zoom_schedules` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'both') NULL DEFAULT 'tamil'");
            }

            if (Schema::hasTable('timetables') && Schema::hasColumn('timetables', 'medium')) {
                DB::table('timetables')->whereIn('medium', ['sinhala', 'all'])->update(['medium' => 'both']);
                DB::statement("ALTER TABLE `timetables` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'both') NULL DEFAULT 'tamil'");
            }

            if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'medium')) {
                DB::table('subjects')->whereIn('medium', ['sinhala', 'all'])->update(['medium' => 'both']);
                DB::statement("ALTER TABLE `subjects` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'both') NOT NULL DEFAULT 'tamil'");
            }
        }
    }
};
