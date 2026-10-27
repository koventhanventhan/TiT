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
        // 1. Add kind and meta to payments table safely (MySQL & SQLite compatible)
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'kind')) {
                $table->string('kind', 32)->default('monthly')->after('payment_method');
            }
            if (!Schema::hasColumn('payments', 'meta')) {
                $table->json('meta')->nullable()->after('kind');
            }
        });

        // 2. Create subject_topups table safely
        if (!Schema::hasTable('subject_topups')) {
            Schema::create('subject_topups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institute_id')->default(1)->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('year_month', 7)->index();
                $table->json('added_subjects');
                $table->json('previous_subjects')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('status', 32)->default('pending')->index(); // pending, paid, cancelled, superseded
                $table->string('payment_method', 32)->nullable(); // online, offline
                $table->unsignedBigInteger('payment_id')->nullable()->index();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_topups');

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('payments', 'kind')) {
                $table->dropColumn('kind');
            }
        });
    }
};
