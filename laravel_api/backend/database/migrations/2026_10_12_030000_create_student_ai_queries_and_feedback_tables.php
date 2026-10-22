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
        if (!Schema::hasTable('student_ai_queries')) {
        Schema::create('student_ai_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('subject')->nullable()->index();
            $table->text('question');
            $table->string('question_language', 10)->default('en')->index(); // 'en', 'ta', 'si'
            $table->longText('answer')->nullable();
            $table->json('retrieved_material_ids')->nullable();
            $table->json('retrieved_chunk_ids')->nullable();
            $table->decimal('top_similarity_score', 6, 5)->nullable();
            $table->string('status', 30)->default('answered')->index(); // 'answered', 'no_notes_found', 'error', 'rate_limited'
            $table->enum('feedback_rating', ['up', 'down'])->nullable()->index();
            $table->text('feedback_comment')->nullable();
            $table->timestamp('feedback_at')->nullable();
            $table->timestamps();

            // Indexes for review dashboard and daily quota calculation
            $table->index(['user_id', 'created_at']);
            $table->index(['institute_id', 'created_at']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_ai_queries');
    }
};
