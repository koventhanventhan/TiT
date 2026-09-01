<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- 1. attendances table ---
        
        // A. Drop old foreign key constraint on user_id
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // B. Rename user_id -> student_id and make it NULLABLE
        //    (Teacher attendance rows won't have a student_id)
        Schema::table('attendances', function (Blueprint $table) {
            $table->renameColumn('user_id', 'student_id');
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->change();
        });

        // C. Migrate existing data
        //    - Student rows: map old user_id to new students.id
        //    - Teacher/non-student rows: set to NULL (they don't belong to a student)
        DB::statement('UPDATE attendances SET student_id = (SELECT id FROM students WHERE students.user_id = attendances.student_id LIMIT 1) WHERE role = "student"');
        DB::statement('UPDATE attendances SET student_id = NULL WHERE role != "student"');

        // D. Add new foreign key constraint (student_id -> students.id)
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->unique(['zoom_schedule_id', 'student_id']);
        });


        // --- 2. assignment_submissions table ---

        // A. Drop old foreign key (student_id currently references users table)
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropForeign(['student_id']); 
        });

        // B. Migrate existing data (map old user_id to new students.id)
        DB::statement('UPDATE assignment_submissions SET student_id = (SELECT id FROM students WHERE students.user_id = assignment_submissions.student_id LIMIT 1)');

        // C. Add new foreign key (student_id -> students.id)
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('use_student_id', function (Blueprint $table) {
            //
        });
    }
};
