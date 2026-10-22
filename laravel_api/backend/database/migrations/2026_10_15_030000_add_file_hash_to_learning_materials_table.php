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
        if (Schema::hasTable('learning_materials') && !Schema::hasColumn('learning_materials', 'file_hash')) {
            Schema::table('learning_materials', function (Blueprint $table) {
                $table->string('file_hash', 64)->nullable()->after('file_path')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('learning_materials') && Schema::hasColumn('learning_materials', 'file_hash')) {
            Schema::table('learning_materials', function (Blueprint $table) {
                $table->dropColumn('file_hash');
            });
        }
    }
};