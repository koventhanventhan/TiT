<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure institutes.rag_enabled defaults to false and set all existing institutes to false
        if (Schema::hasTable('institutes')) {
            if (!Schema::hasColumn('institutes', 'rag_enabled')) {
                Schema::table('institutes', function (Blueprint $table) {
                    $table->boolean('rag_enabled')->default(false)->after('status');
                });
            }
            DB::table('institutes')->update(['rag_enabled' => false]);
        }

        // 2. Add is_ocr flag to material_chunks
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
        if (Schema::hasTable('material_chunks') && Schema::hasColumn('material_chunks', 'is_ocr')) {
            Schema::table('material_chunks', function (Blueprint $table) {
                $table->dropColumn('is_ocr');
            });
        }
    }
};
