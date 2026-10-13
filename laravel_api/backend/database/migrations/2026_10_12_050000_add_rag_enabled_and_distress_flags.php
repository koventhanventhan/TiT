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
        // 1. Add per-institute RAG switch to institutes table (default FALSE)
        if (Schema::hasTable('institutes')) {
            if (!Schema::hasColumn('institutes', 'rag_enabled')) {
                Schema::table('institutes', function (Blueprint $table) {
                    $table->boolean('rag_enabled')->default(false)->after('status');
                });
            }
            // Set all existing institutes to false
            \Illuminate\Support\Facades\DB::table('institutes')->update(['rag_enabled' => false]);
        }

        // 2. Add admin review & distress flags to student_ai_queries table
        if (Schema::hasTable('student_ai_queries')) {
            Schema::table('student_ai_queries', function (Blueprint $table) {
                if (!Schema::hasColumn('student_ai_queries', 'is_flagged')) {
                    $table->boolean('is_flagged')->default(false)->index()->after('status');
                }
                if (!Schema::hasColumn('student_ai_queries', 'flag_reason')) {
                    $table->string('flag_reason')->nullable()->after('is_flagged');
                }
            });
        }

        // 3. Add is_ocr flag to material_chunks to track pages extracted via OCR fallback
        if (Schema::hasTable('material_chunks') && !Schema::hasColumn('material_chunks', 'is_ocr')) {
            Schema::table('material_chunks', function (Blueprint $table) {
                $table->boolean('is_ocr')->default(false)->after('word_count');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('institutes') && Schema::hasColumn('institutes', 'rag_enabled')) {
            Schema::table('institutes', function (Blueprint $table) {
                $table->dropColumn('rag_enabled');
            });
        }

        if (Schema::hasTable('student_ai_queries')) {
            Schema::table('student_ai_queries', function (Blueprint $table) {
                if (Schema::hasColumn('student_ai_queries', 'is_flagged')) {
                    $table->dropColumn('is_flagged');
                }
                if (Schema::hasColumn('student_ai_queries', 'flag_reason')) {
                    $table->dropColumn('flag_reason');
                }
            });
        }
    }
};
