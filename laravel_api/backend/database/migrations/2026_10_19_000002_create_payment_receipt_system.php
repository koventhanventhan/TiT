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
        // 1. Add receipt columns to payments table
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'receipt_no')) {
                $table->string('receipt_no', 40)->nullable()->after('gateway_ref');
            }
            if (!Schema::hasColumn('payments', 'receipt_sent_at')) {
                $table->timestamp('receipt_sent_at')->nullable()->after('receipt_no');
            }
        });

        // Add unique index on (institute_id, receipt_no)
        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->unique(['institute_id', 'receipt_no'], 'payments_institute_receipt_unique');
            } catch (\Throwable $e) {
                // Ignore if index already exists
            }
        });

        // 2. Create payment_receipt_counters table
        if (!Schema::hasTable('payment_receipt_counters')) {
            Schema::create('payment_receipt_counters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institute_id')->default(1);
                $table->unsignedSmallInteger('receipt_year');
                $table->unsignedInteger('last_seq')->default(0);
                $table->timestamps();

                $table->unique(['institute_id', 'receipt_year'], 'pr_counters_inst_year_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_receipt_counters');

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropUnique('payments_institute_receipt_unique');
            } catch (\Throwable $e) {
                // Ignore
            }

            if (Schema::hasColumn('payments', 'receipt_sent_at')) {
                $table->dropColumn('receipt_sent_at');
            }
            if (Schema::hasColumn('payments', 'receipt_no')) {
                $table->dropColumn('receipt_no');
            }
        });
    }
};
