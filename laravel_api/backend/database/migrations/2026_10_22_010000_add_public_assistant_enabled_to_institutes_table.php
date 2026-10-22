<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('institutes') && !Schema::hasColumn('institutes', 'public_assistant_enabled')) {
            Schema::table('institutes', function (Blueprint $table) {
                $table->boolean('public_assistant_enabled')->default(false)->after('rag_enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('institutes') && Schema::hasColumn('institutes', 'public_assistant_enabled')) {
            Schema::table('institutes', function (Blueprint $table) {
                $table->dropColumn('public_assistant_enabled');
            });
        }
    }
};
