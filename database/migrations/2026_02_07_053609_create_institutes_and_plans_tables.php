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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('yearly_price', 10, 2);
            $table->integer('max_students')->default(-1); // -1 for unlimited
            $table->integer('max_teachers')->default(-1);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('institutes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->json('theme_settings')->nullable();
            $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutes');
        Schema::dropIfExists('subscription_plans');
    }
};
