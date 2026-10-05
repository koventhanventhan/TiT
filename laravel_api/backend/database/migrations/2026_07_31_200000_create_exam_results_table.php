<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->string('index_no', 50);
            $table->string('term', 50);           // e.g. "1st Term", "2nd Term", "Mid-Term" — admin managed
            $table->string('grade', 50);          // e.g. "Grade-10", "O/L", "A/L"
            $table->string('subject');
            $table->decimal('marks', 5, 2)->nullable();
            $table->string('result_grade', 20)->nullable(); // A+, A, A-, B+, B, B-, C+, C, S, W, F
            $table->integer('rank')->nullable();
            $table->string('year', 20)->nullable();
            $table->foreignId('institute_id')->nullable()->constrained('institutes')->onDelete('cascade');
            $table->timestamps();

            // Index for fast searching
            $table->index(['index_no', 'term', 'grade']);
            $table->index(['term', 'grade', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
