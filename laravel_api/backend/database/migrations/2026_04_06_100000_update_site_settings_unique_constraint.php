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
        Schema::table('site_settings', function (Blueprint $table) {
            // Drop the old global unique constraint on 'key'
            // The default name is 'site_settings_key_unique'
            $table->dropUnique(['key']);
            
            // Add the new composite unique constraint on ['key', 'institute_id']
            $table->unique(['key', 'institute_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Revert changes
            $table->dropUnique(['key', 'institute_id']);
            $table->unique('key');
        });
    }
};
