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
        // 1. Move any existing data (just in case there are records)
        \Illuminate\Support\Facades\DB::statement('UPDATE users SET phone_number = parent_phone_number WHERE phone_number IS NULL AND parent_phone_number IS NOT NULL');

        // 2. Drop the redundant column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('parent_phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('parent_phone_number')->nullable()->after('phone_number');
        });
    }
};
