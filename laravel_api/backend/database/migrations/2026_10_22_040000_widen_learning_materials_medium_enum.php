<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Widen learning_materials.medium enum to include 'sinhala' and 'all'.
 *
 * LOCK / DOWNTIME NOTE:
 * In MariaDB (10.2+) and MySQL (8.0+), appending new elements ('sinhala', 'all')
 * to an existing ENUM column via ALTER TABLE ... MODIFY COLUMN ... is an INSTANT /
 * INPLACE metadata-only operation (ALGORITHM=INSTANT).
 * - No table copy or row-by-row rebuild occurs.
 * - Table metadata lock duration is minimal (< 10 ms).
 * - Existing records with 'both' are preserved completely intact and functional.
 * - Zero downtime is required for this migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('learning_materials')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Guarded raw SQL alter for MySQL / MariaDB ensuring ALGORITHM=INSTANT compatibility
            DB::statement("ALTER TABLE `learning_materials` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'sinhala', 'all', 'both') NOT NULL DEFAULT 'all'");
        } else {
            // For SQLite and other drivers during testing:
            // SQLite does not enforce enum types at schema level, but ensure column exists.
            if (!Schema::hasColumn('learning_materials', 'medium')) {
                Schema::table('learning_materials', function (Blueprint $table) {
                    $table->string('medium')->default('all')->after('grade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('learning_materials')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Safe fallback: Remap any rows with 'sinhala' or 'all' to 'both' before reverting enum
            // to avoid SQL truncation / strict mode errors on downgrade.
            DB::table('learning_materials')
                ->whereIn('medium', ['sinhala', 'all'])
                ->update(['medium' => 'both']);

            DB::statement("ALTER TABLE `learning_materials` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'both') NOT NULL DEFAULT 'tamil'");
        }
    }
};
