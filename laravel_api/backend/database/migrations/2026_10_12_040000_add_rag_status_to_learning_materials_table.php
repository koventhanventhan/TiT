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
        Schema::table('learning_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('learning_materials', 'rag_status')) {
                $table->string('rag_status', 20)->default('pending')->after('description')->index();
            }
            if (!Schema::hasColumn('learning_materials', 'rag_error')) {
                $table->text('rag_error')->nullable()->after('rag_status');
            }
            if (!Schema::hasColumn('learning_materials', 'rag_indexed_at')) {
                $table->timestamp('rag_indexed_at')->nullable()->after('rag_error');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('learning_materials', 'rag_status')) {
                $columnsToDrop[] = 'rag_status';
            }
            if (Schema::hasColumn('learning_materials', 'rag_error')) {
                $columnsToDrop[] = 'rag_error';
            }
            if (Schema::hasColumn('learning_materials', 'rag_indexed_at')) {
                $columnsToDrop[] = 'rag_indexed_at';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
