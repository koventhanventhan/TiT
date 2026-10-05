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
            $table->timestamp('admin_confirmed_at')->nullable()->after('updated_at');
            $table->string('registration_status', 50)->nullable()->default('pending_payment')->after('admin_confirmed_at');
            $table->timestamp('deactivated_at')->nullable()->after('registration_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['admin_confirmed_at', 'registration_status', 'deactivated_at']);
        });
    }
};
