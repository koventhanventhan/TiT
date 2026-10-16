<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAssignmentLeakTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'Apex Institute',
            'slug' => 'apex-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute->id,
            'name' => 'Prof. Charles',
            'full_name' => 'Charles Xavier',
            'email' => 'charles.secret@tit.edu',
            'phone_number' => '+94779998877',
            'date_of_birth' => '1975-07-13',
            'location' => 'Westchester, NY',
            'gender' => 'male',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Peter Parker',
            'email' => 'peter@student.tit.edu',
            'registration_status' => 'approved',
            'current_grade' => 'Grade 10',
            'medium' => 'tamil',
            'admin_confirmed_at' => now(),
        ]);

        Payment::create([
            'user_id' => $this->student->id,
            'year_month' => now()->format('Y-m'),
            'status' => 'paid',
            'paid_at' => now(),
            'amount' => 1000,
            'payment_type' => 'monthly',
            'institute_id' => $this->institute->id,
        ]);
    }

    public function test_student_assignments_whitelists_fields_and_does_not_leak_teacher_private_info(): void
    {
        $assignment = Assignment::create([
            'title' => 'Trigonometry Homework 1',
            'description' => 'Complete problems 1 through 10 on page 42.',
            'due_date' => Carbon::now()->addDays(5),
            'teacher_id' => $this->teacher->id,
            'grade' => 'Grade 10',
            'subject' => 'Mathematics',
            'institute_id' => $this->institute->id,
            'file_path' => 'assignments/math_hw1.pdf',
        ]);

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'file_path' => 'submissions/peter_math_hw1.pdf',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/student/assignments');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);

        $item = $data[0];

        // 1. Whitelisted assignment fields
        $this->assertEquals($assignment->id, $item['id']);
        $this->assertEquals('Trigonometry Homework 1', $item['title']);
        $this->assertEquals('Grade 10', $item['grade']);
        $this->assertEquals('Mathematics', $item['subject']);
        $this->assertEquals($this->teacher->id, $item['teacher_id']);
        $this->assertArrayHasKey('due_date', $item);
        $this->assertArrayHasKey('file_url', $item);
        $this->assertArrayHasKey('teacher', $item);
        $this->assertArrayHasKey('submissions', $item);

        // 2. Whitelisted teacher fields: only id, name, full_name
        $teacher = $item['teacher'];
        $this->assertNotNull($teacher);
        $this->assertEquals($this->teacher->id, $teacher['id']);
        $this->assertEquals('Prof. Charles', $teacher['name']);
        $this->assertEquals('Charles Xavier', $teacher['full_name']);

        // Explicitly assert private teacher fields are NOT in teacher array
        $this->assertArrayNotHasKey('email', $teacher, "Teacher's email leaked in /api/student/assignments");
        $this->assertArrayNotHasKey('phone_number', $teacher, "Teacher's phone_number leaked in /api/student/assignments");
        $this->assertArrayNotHasKey('date_of_birth', $teacher, "Teacher's date_of_birth leaked in /api/student/assignments");
        $this->assertArrayNotHasKey('location', $teacher, "Teacher's location leaked in /api/student/assignments");
        $this->assertArrayNotHasKey('gender', $teacher, "Teacher's gender leaked in /api/student/assignments");
        $this->assertArrayNotHasKey('password', $teacher, "Teacher's password hash leaked in /api/student/assignments");
        $this->assertArrayNotHasKey('remember_token', $teacher, "Teacher's remember_token leaked in /api/student/assignments");

        // 3. Submissions whitelist
        $this->assertCount(1, $item['submissions']);
        $sub = $item['submissions'][0];
        $this->assertEquals($this->student->id, $sub['student_id']);
        $this->assertArrayHasKey('status', $sub);
        $this->assertArrayHasKey('file_url', $sub);

        // 4. Raw response string assertions - absolute guarantee that private values never appear anywhere
        $raw = $response->getContent();
        $this->assertStringNotContainsString('charles.secret@tit.edu', $raw);
        $this->assertStringNotContainsString('+94779998877', $raw);
        $this->assertStringNotContainsString('1975-07-13', $raw);
        $this->assertStringNotContainsString('Westchester', $raw);
    }
}