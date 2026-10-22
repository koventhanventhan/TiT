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
        if (!Schema::hasTable('material_pages')) {
            Schema::create('material_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
                $table->unsignedInteger('page_number');
                $table->longText('text');
                $table->boolean('is_ocr')->default(false);
                $table->boolean('ocr_uncertain')->default(false);
                $table->string('file_hash', 64)->nullable()->index();
                $table->timestamps();

                $table->unique(['material_id', 'page_number']);
                $table->index(['file_hash', 'page_number']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('material_pages')) {
            Schema::dropIfExists('material_pages');
        }
    }
};