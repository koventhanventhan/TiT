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
        // 1. Add index_prefix and index_seq_start to institutes if not present
        if (Schema::hasTable('institutes')) {
            Schema::table('institutes', function (Blueprint $table) {
                if (!Schema::hasColumn('institutes', 'index_prefix')) {
                    $table->string('index_prefix', 20)->default('TiT')->after('slug');
                }
                if (!Schema::hasColumn('institutes', 'index_seq_start')) {
                    $table->unsignedInteger('index_seq_start')->default(100)->after('index_prefix');
                }
            });
        }

        // 2. Create student_index_counters table
        if (!Schema::hasTable('student_index_counters')) {
            Schema::create('student_index_counters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institute_id')->default(1);
                $table->unsignedTinyInteger('batch_year');
                $table->unsignedInteger('last_seq')->default(0);
                $table->timestamps();

                $table->unique(['institute_id', 'batch_year'], 'idx_counters_inst_batch_unique');
            });
        }

        // 3. Add batch_year, index_seq, index_number to users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'batch_year')) {
                    $table->unsignedTinyInteger('batch_year')->nullable()->after('current_grade');
                }
                if (!Schema::hasColumn('users', 'index_seq')) {
                    $table->unsignedInteger('index_seq')->nullable()->after('batch_year');
                }
                if (!Schema::hasColumn('users', 'index_number')) {
                    $table->string('index_number', 40)->nullable()->after('index_seq');
                }
                $table->unique(['institute_id', 'index_number'], 'users_institute_index_number_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_institute_index_number_unique');
                $table->dropColumn(['batch_year', 'index_seq', 'index_number']);
            });
        }

        Schema::dropIfExists('student_index_counters');

        if (Schema::hasTable('institutes')) {
            Schema::table('institutes', function (Blueprint $table) {
                $table->dropColumn(['index_prefix', 'index_seq_start']);
            });
        }
    }
};
