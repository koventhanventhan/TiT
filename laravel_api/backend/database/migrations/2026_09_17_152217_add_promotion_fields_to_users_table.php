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
            $table->boolean('needs_subject_review')->default(false)->after('selected_subjects');
            $table->boolean('is_graduated')->default(false)->after('needs_subject_review');
            $table->timestamp('last_promoted_at')->nullable()->after('is_graduated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['needs_subject_review', 'is_graduated', 'last_promoted_at']);
        });
    }
};
