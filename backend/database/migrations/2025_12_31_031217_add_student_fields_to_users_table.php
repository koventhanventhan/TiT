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
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('name');
            $table->date('date_of_birth')->nullable()->after('full_name');
            $table->enum('gender', ['male', 'female'])->nullable()->after('date_of_birth');
            $table->string('school_name')->nullable()->after('gender');
            $table->enum('medium', ['english', 'tamil'])->nullable()->after('school_name');
            $table->boolean('online_experience')->nullable()->after('medium');
            $table->string('device_used')->nullable()->after('online_experience'); // JSON or comma-separated
            $table->string('current_grade')->nullable()->after('device_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'date_of_birth',
                'gender',
                'school_name',
                'medium',
                'online_experience',
                'device_used',
                'current_grade'
            ]);
        });
    }
};
