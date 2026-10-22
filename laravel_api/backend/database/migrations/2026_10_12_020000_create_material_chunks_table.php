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
        if (!Schema::hasTable('material_chunks')) {
        Schema::create('material_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('learning_materials')->onDelete('cascade');
            $table->string('grade')->nullable()->index();
            $table->string('medium')->nullable()->index();
            $table->string('subject')->nullable()->index();
            $table->string('chunk_type')->default('page')->index(); // 'page', 'qa', 'section'
            $table->unsignedInteger('page_number')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('term')->nullable();
            $table->string('question_number')->nullable();
            $table->longText('content');
            $table->string('embedding_model');
            $table->unsignedInteger('dimensions');
            $table->binary('embedding'); // Packed 32-bit float array (f*)
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamps();

            // Composite indexes for rapid scoped retrieval
            $table->index(['institute_id', 'grade']);
            $table->index(['material_id', 'page_number']);
        });

        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `material_chunks` MODIFY `embedding` MEDIUMBLOB NOT NULL');
        }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_chunks');
    }
};
