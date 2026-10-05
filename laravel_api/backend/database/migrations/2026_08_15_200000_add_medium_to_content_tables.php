<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'medium' column to subjects, timetables, learning_materials, and zoom_schedules.
     * This enables filtering content by student's medium (English / Tamil).
     */
    public function up(): void
    {
        // Subjects: english, tamil, or both (some subjects like English Language are common)
        Schema::table('subjects', function (Blueprint $table) {
            $table->enum('medium', ['english', 'tamil', 'both'])->default('tamil')->after('category');
        });

        // Timetables: each class schedule is for a specific medium
        Schema::table('timetables', function (Blueprint $table) {
            $table->enum('medium', ['english', 'tamil'])->default('tamil')->after('grade');
        });

        // Learning Materials: notes, past papers, recordings per medium
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->enum('medium', ['english', 'tamil', 'both'])->default('tamil')->after('grade');
        });

        // Zoom Schedules: each zoom class is for a specific medium
        Schema::table('zoom_schedules', function (Blueprint $table) {
            $table->enum('medium', ['english', 'tamil'])->default('tamil')->after('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('medium');
        });

        Schema::table('timetables', function (Blueprint $table) {
            $table->dropColumn('medium');
        });

        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropColumn('medium');
        });

        Schema::table('zoom_schedules', function (Blueprint $table) {
            $table->dropColumn('medium');
        });
    }
};
