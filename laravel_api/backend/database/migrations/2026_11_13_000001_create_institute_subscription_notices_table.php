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
        Schema::create('institute_subscription_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
            $table->string('kind'); // '14_days', '7_days', '3_days', '1_day', 'today'
            $table->date('expires_on');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['institute_id', 'kind', 'expires_on'], 'inst_sub_notices_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institute_subscription_notices');
    }
};

