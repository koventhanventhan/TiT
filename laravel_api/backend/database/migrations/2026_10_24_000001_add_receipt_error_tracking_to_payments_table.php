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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'receipt_last_error')) {
                $table->text('receipt_last_error')->nullable()->after('receipt_sent_at');
            }
            if (!Schema::hasColumn('payments', 'receipt_attempted_at')) {
                $table->timestamp('receipt_attempted_at')->nullable()->after('receipt_last_error');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'receipt_attempted_at')) {
                $table->dropColumn('receipt_attempted_at');
            }
            if (Schema::hasColumn('payments', 'receipt_last_error')) {
                $table->dropColumn('receipt_last_error');
            }
        });
    }
};
