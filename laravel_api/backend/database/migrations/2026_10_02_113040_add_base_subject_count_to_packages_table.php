<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('base_subject_count')->nullable()->after('addon_price');
        });

        // Update existing main_subjects packages to have base_subject_count = 5
        DB::table('packages')
            ->where('type', 'main_subjects')
            ->update(['base_subject_count' => 5]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('base_subject_count');
        });
    }
};
