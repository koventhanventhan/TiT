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
        if (Schema::hasTable('material_pages')) {
            Schema::table('material_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('material_pages', 'teacher_verified')) {
                    $table->boolean('teacher_verified')->default(false)->after('ocr_uncertain')->index();
                }
                if (!Schema::hasColumn('material_pages', 'last_edited_by')) {
                    $table->unsignedBigInteger('last_edited_by')->nullable()->after('teacher_verified');
                    $table->foreign('last_edited_by')->references('id')->on('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('material_pages', 'last_edited_at')) {
                    $table->timestamp('last_edited_at')->nullable()->after('last_edited_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('material_pages')) {
            Schema::table('material_pages', function (Blueprint $table) {
                if (Schema::hasColumn('material_pages', 'last_edited_by')) {
                    $table->dropForeign(['last_edited_by']);
                    $table->dropColumn('last_edited_by');
                }
                if (Schema::hasColumn('material_pages', 'teacher_verified')) {
                    $table->dropColumn('teacher_verified');
                }
                if (Schema::hasColumn('material_pages', 'last_edited_at')) {
                    $table->dropColumn('last_edited_at');
                }
            });
        }
    }
};
