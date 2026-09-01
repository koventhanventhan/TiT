<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing users to students table
        $users = DB::table('users')->where('role', 'user')->get();

        foreach ($users as $user) {
            DB::table('students')->insert([
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'school_name' => $user->school_name,
                'medium' => $user->medium,
                'current_grade' => $user->current_grade,
                'stream' => $user->stream,
                'selected_subjects' => $user->selected_subjects,
                'custom_fields' => $user->custom_fields,
                'created_at' => $user->created_at ?? Carbon::now(),
                'updated_at' => $user->updated_at ?? Carbon::now(),
            ]);
        }
        
        // Note: We are deliberately NOT dropping the student-related columns from the `users` table yet.
        // This is part of the Safe Rollback Plan. We will drop them in a future cleanup migration.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear all students that were migrated
        DB::table('students')->truncate();
    }
};
