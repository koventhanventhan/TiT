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
        if (!Schema::hasTable('platform_gemini_usages')) {
            Schema::create('platform_gemini_usages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institute_id')->index();
                $table->date('usage_date')->index();
                $table->unsignedInteger('requests_count')->default(0);
                $table->timestamps();

                $table->unique(['institute_id', 'usage_date'], 'inst_platform_gemini_usage_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_gemini_usages');
    }
};

