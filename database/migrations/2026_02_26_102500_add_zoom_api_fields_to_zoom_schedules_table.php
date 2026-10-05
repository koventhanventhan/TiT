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
        Schema::table('zoom_schedules', function (Blueprint $table) {
            $table->string('meeting_id')->nullable()->after('zoom_link');
            $table->text('start_url')->nullable()->after('meeting_id');
            $table->text('join_url')->nullable()->after('start_url');
            $table->string('password')->nullable()->after('join_url');
            $table->integer('duration')->default(60)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_schedules', function (Blueprint $table) {
            $table->dropColumn(['meeting_id', 'start_url', 'join_url', 'password', 'duration']);
        });
    }
};
