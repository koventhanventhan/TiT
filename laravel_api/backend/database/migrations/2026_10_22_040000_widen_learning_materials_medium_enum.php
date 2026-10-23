<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Widen learning_materials.medium enum to include 'sinhala' and 'all'.
 *
 * ENUM STORAGE & MIGRATION IMPACT ANALYSIS:
 * 1. Storage & Enum Indexes:
 *    In MySQL/MariaDB, ENUM values are stored internally as 1-based integers mapping to
 *    their declared order:
 *      1 = 'english', 2 = 'tamil', 3 = 'both'
 *    Appending new values strictly at the END of the list:
 *      ('english', 'tamil', 'both', 'sinhala', 'all')
 *    preserves existing index mappings (1, 2, 3) intact without renumbering existing rows.
 * 2. Locking & Execution Reality (Correction of false "instant" claim):
 *    - In MySQL 8.0+ and MariaDB 10.2+, appending elements to the end of an ENUM is an in-place
 *      operation (ALGORITHM=INSTANT in MySQL 8.0).
 *    - However, on MariaDB (e.g. 10.4), altering column attributes with a default change or
 *      table definition may still take an exclusive metadata lock (MDL) and may require a table copy
 *      if table constraints or strict mode require validation.
 *    - On large production tables, this brief metadata lock can block incoming reads/writes.
 *      Zero downtime cannot be blindly claimed without verifying table engine and concurrent traffic.
 * 3. Default Recommendation:
 *    - The default must remain 'tamil' (matching historical TiT Academy baseline) and NOT 'all'.
 *    - Defaulting to 'all' is dangerous: if an instructor uploads notes without specifying a medium,
 *      defaulting to 'all' would accidentally expose language-specific notes to every student.
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
            // Guarded raw SQL alter appending new values at the END of the ENUM list
            DB::statement("ALTER TABLE `learning_materials` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'both', 'sinhala', 'all') NOT NULL DEFAULT 'tamil'");
        } else {
            // For SQLite and other drivers during testing:
            if (!Schema::hasColumn('learning_materials', 'medium')) {
                Schema::table('learning_materials', function (Blueprint $table) {
                    $table->string('medium')->default('tamil')->after('grade');
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
