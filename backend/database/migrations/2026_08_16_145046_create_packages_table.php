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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ta')->nullable();
            $table->string('name_si')->nullable();
            $table->string('category');
            $table->enum('medium', ['tamil', 'english', 'both'])->default('tamil');
            $table->decimal('package_price', 10, 2);
            $table->decimal('original_price', 10, 2)->default(0);
            $table->decimal('addon_price', 10, 2)->nullable();
            $table->string('type')->default('all_subjects'); // all_subjects, main_subjects
            $table->json('applicable_grades');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
