<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\Payment;
use App\Models\User;
use App\Models\ZoomSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $teacherA;
    private User $teacherB;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'Apex Institute',
            'slug' => 'apex-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->teacherA = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute->id,
            'name' => 'Teacher Alpha',
            'email' => 'alpha@test.com',
        ]);

        $this->teacherB = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute->id,
            'name' => 'Teacher Beta',
            'email' => 'beta@test.com',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Student One',
            'email' => 'student1@test.com',
            'registration_status' => 'approved',
            'current_grade' => 'Grade 4',
            'admin_confirmed_at' => now(),
        ]);

        Payment::create([
            'user_id' => $this->student->id,
            'year_month' => now()->format('Y-m'),
            'status' => 'paid',
            'amount' => 1000,
            'payment_type' => 'monthly',
            'institute_id' => $this->institute->id,
        ]);
    }

    public function test_teacher_cannot_delete_another_teachers_material(): void
    {
        $material = LearningMaterial::create([
            'title' => 'Alpha Notes',
            'type' => 'note',
            'teacher_id' => $this->teacherA->id,
            'institute_id' => $this->institute->id,
        ]);

        Sanctum::actingAs($this->teacherB);
        $response = $this->deleteJson("/api/teacher/materials/{$material->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('learning_materials', ['id' => $material->id]);
    }

    public function test_teacher_can_delete_own_material(): void
    {
        Storage::fake('public');
        $fakeFile = UploadedFile::fake()->create('note.pdf', 50);
        $path = $fakeFile->store('materials', 'public');

        $material = LearningMaterial::create([
            'title' => 'Alpha Notes to Delete',
            'type' => 'note',
            'file_path' => $path,
            'teacher_id' => $this->teacherA->id,
            'institute_id' => $this->institute->id,
        ]);

        Sanctum::actingAs($this->teacherA);
        $response = $this->deleteJson("/api/teacher/materials/{$material->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('learning_materials', ['id' => $material->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_teacher_dashboard_stats_returns_all_expected_fields(): void
    {
        // Add pending student and approved student to verify filtering
        User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'pending',
            'current_grade' => 'Grade 4',
        ]);

        User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'payment_completed',
            'current_grade' => 'Grade 4',
        ]);

        // Schedule today and this week
        $todayClass = ZoomSchedule::create([
            'title' => 'Physics Class',
            'zoom_link' => 'https://zoom.us/j/123456789',
            'scheduled_at' => Carbon::now()->addHour(),
            'duration' => 60,
            'institute_id' => $this->institute->id,
        ]);
        $todayClass->teachers()->attach($this->teacherA->id);

        // Assignment + submissions
        $assignment = Assignment::create([
            'title' => 'Calculus Task',
            'teacher_id' => $this->teacherA->id,
            'institute_id' => $this->institute->id,
            'grade' => '4',
        ]);

        $sub1 = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'status' => 'graded',
            'marks' => 95,
            'submitted_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($this->teacherA);
        $response = $this->getJson('/api/teacher/stats');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('today_classes', $data);
        $this->assertArrayHasKey('upcoming_classes', $data);
        $this->assertArrayHasKey('week_classes', $data);
        $this->assertArrayHasKey('total_students', $data);
        $this->assertArrayHasKey('new_submissions', $data);
        $this->assertArrayHasKey('pending_assignments', $data);
        $this->assertArrayHasKey('total_assignments', $data);
        $this->assertArrayHasKey('total_submissions', $data);
        $this->assertArrayHasKey('recent_submissions', $data);
        $this->assertArrayHasKey('user', $data);

        // Approved students count: 1 from setUp (student) + 1 payment_completed = 2 (pending ignored)
        $this->assertEquals(2, $data['total_students']);
        $this->assertEquals(1, $data['total_assignments']);
        $this->assertEquals(2, $data['total_submissions']);
        $this->assertEquals(1, $data['new_submissions']);
        $this->assertEquals(1, $data['pending_assignments']);
        $this->assertCount(1, $data['recent_submissions']);
        $this->assertEquals('Calculus Task', $data['recent_submissions'][0]['assignment_title']);
        $this->assertEquals($this->student->name, $data['recent_submissions'][0]['student_name']);
    }

    public function test_assignment_list_includes_total_students_with_grade_matching(): void
    {
        // 2 approved grade 4 students
        User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'approved',
            'current_grade' => '4',
        ]);

        // 1 approved grade 5 student
        User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'approved',
            'current_grade' => 'grade-5',
        ]);

        $assignGrade4 = Assignment::create([
            'title' => 'Grade 4 Science',
            'teacher_id' => $this->teacherA->id,
            'grade' => '4',
            'institute_id' => $this->institute->id,
        ]);

        $assignAllGrades = Assignment::create([
            'title' => 'General Essay',
            'teacher_id' => $this->teacherA->id,
            'grade' => null,
            'institute_id' => $this->institute->id,
        ]);

        Sanctum::actingAs($this->teacherA);
        $response = $this->getJson('/api/teacher/assignments');

        $response->assertStatus(200);
        $list = collect($response->json());

        $item4 = $list->firstWhere('id', $assignGrade4->id);
        $this->assertNotNull($item4);
        // Student in setUp ('Grade 4') + Student created ('4') = 2
        $this->assertEquals(2, $item4['total_students']);

        $itemAll = $list->firstWhere('id', $assignAllGrades->id);
        $this->assertNotNull($itemAll);
        // All approved students = 1 (setUp) + 1 (grade 4) + 1 (grade 5) = 3
        $this->assertEquals(3, $itemAll['total_students']);
    }

    public function test_student_with_grade_4_can_view_assignment_with_grade_4_string(): void
    {
        $assignment = Assignment::create([
            'title' => 'Algebra 101',
            'teacher_id' => $this->teacherA->id,
            'grade' => '4', // teacher saves "4"
            'institute_id' => $this->institute->id,
        ]);

        // Student's current_grade is "Grade 4"
        Sanctum::actingAs($this->student);
        $response = $this->getJson('/api/student/assignments');

        $response->assertStatus(200);
        $ids = collect($response->json())->pluck('id')->all();
        $this->assertContains($assignment->id, $ids);
    }

    public function test_assignment_submission_persists_submitted_at_and_returns_file_url(): void
    {
        Storage::fake('public');

        $assignment = Assignment::create([
            'title' => 'History Homework',
            'teacher_id' => $this->teacherA->id,
            'institute_id' => $this->institute->id,
            'grade' => '4',
        ]);

        Sanctum::actingAs($this->student);
        $fakeFile = UploadedFile::fake()->create('essay.pdf', 100);

        $submitResponse = $this->post("/api/student/assignments/{$assignment->id}/submit", [
            'file' => $fakeFile,
        ], ['Accept' => 'application/json']);

        $submitResponse->assertStatus(200);
        $submissionId = $submitResponse->json('id');

        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submissionId,
            'status' => 'pending',
        ]);
        $sub = AssignmentSubmission::find($submissionId);
        $this->assertNotNull($sub->submitted_at);

        // Teacher views submissions
        Sanctum::actingAs($this->teacherA);
        $teacherResponse = $this->getJson("/api/teacher/assignments/{$assignment->id}/submissions");

        $teacherResponse->assertStatus(200);
        $first = $teacherResponse->json()[0];
        $this->assertNotNull($first['submitted_at']);
        $this->assertNotNull($first['file_url']);
        $this->assertStringContainsString('storage/submissions/', $first['file_url']);
    }

    public function test_grade_submission_aborts_403_for_other_teacher_and_out_of_scope_assignment(): void
    {
        $assignment = Assignment::create([
            'title' => 'Chemistry Lab',
            'teacher_id' => $this->teacherA->id,
            'institute_id' => $this->institute->id,
        ]);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'status' => 'pending',
        ]);

        // Teacher B tries to grade Teacher A's assignment
        Sanctum::actingAs($this->teacherB);
        $response = $this->postJson("/api/teacher/submissions/{$submission->id}/grade", [
            'marks' => 88,
            'feedback' => 'Good job',
        ]);
        $response->assertStatus(403);

        // Now test when assignment is from another institute (out of scope, relation returns null)
        $foreignInstitute = Institute::create([
            'id' => 999,
            'name' => 'Foreign Institute',
            'slug' => 'foreign-inst',
            'domain' => 'foreign.test',
            'status' => 'active',
        ]);

        $foreignTeacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $foreignInstitute->id,
        ]);

        $foreignAssignment = Assignment::create([
            'title' => 'Foreign Math',
            'teacher_id' => $foreignTeacher->id,
            'institute_id' => $foreignInstitute->id,
        ]);

        $foreignSubmission = AssignmentSubmission::create([
            'assignment_id' => $foreignAssignment->id,
            'student_id' => $this->student->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->teacherA);
        // Since foreignAssignment belongs to institute 999, $foreignSubmission->assignment resolves to null
        $responseForeign = $this->postJson("/api/teacher/submissions/{$foreignSubmission->id}/grade", [
            'marks' => 70,
        ]);
        // Must return 403, NOT 500 TypeError
        $responseForeign->assertStatus(403);
    }

    public function test_teacher_zoom_attend_authorization_and_duration_in_index(): void
    {
        $schedule = ZoomSchedule::create([
            'title' => 'Maths Revision',
            'zoom_link' => 'https://zoom.us/j/123456789',
            'scheduled_at' => Carbon::now()->addHour(),
            'duration' => 90,
            'institute_id' => $this->institute->id,
        ]);
        // Assigned only to teacherA
        $schedule->teachers()->attach($this->teacherA->id);

        // Teacher B attempts to attend -> 403
        Sanctum::actingAs($this->teacherB);
        $resB = $this->postJson('/api/teacher/attend', [
            'zoom_schedule_id' => $schedule->id,
        ]);
        $resB->assertStatus(403);

        // Teacher A attends -> 200
        Sanctum::actingAs($this->teacherA);
        $resA = $this->postJson('/api/teacher/attend', [
            'zoom_schedule_id' => $schedule->id,
        ]);
        $resA->assertStatus(200);

        // Check index contains duration
        $indexRes = $this->getJson('/api/teacher/zoom-classes');
        $indexRes->assertStatus(200);
        $item = collect($indexRes->json('data'))->firstWhere('id', $schedule->id);
        $this->assertEquals(90, $item['duration']);
    }

    public function test_teacher_student_show_returns_whitelisted_fields_only(): void
    {
        Sanctum::actingAs($this->teacherA);
        $response = $this->getJson("/api/teacher/students/{$this->student->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('profile', $data);
        $this->assertArrayHasKey('attendance', $data);

        $profile = $data['profile'];
        $expectedKeys = ['id', 'name', 'email', 'phone_number', 'current_grade', 'stream', 'registration_status'];
        sort($expectedKeys);
        $actualKeys = array_keys($profile);
        sort($actualKeys);

        $this->assertEquals($expectedKeys, $actualKeys);
        $this->assertArrayNotHasKey('password', $profile);
        $this->assertArrayNotHasKey('plain_password', $profile);
        $this->assertArrayNotHasKey('remember_token', $profile);
    }

    public function test_materials_download_security(): void
    {
        // Path traversal with ..
        $res1 = $this->get('/api/materials/download?path=../../etc/passwd');
        $res1->assertStatus(400);

        // Null byte
        $res2 = $this->get('/api/materials/download?path=test.pdf%00.png');
        $res2->assertStatus(400);

        // Absolute path
        $res3 = $this->get('/api/materials/download?path=/etc/passwd');
        $res3->assertStatus(400);
    }

    public function test_student_role_gets_403_on_teacher_routes(): void
    {
        Sanctum::actingAs($this->student);

        $routes = [
            ['GET', '/api/teacher/stats'],
            ['GET', '/api/teacher/upcoming-schedules'],
            ['GET', '/api/teacher/zoom-classes'],
            ['GET', '/api/teacher/students'],
            ['GET', "/api/teacher/students/{$this->student->id}"],
            ['GET', '/api/teacher/assignments'],
            ['POST', '/api/teacher/assignments'],
            ['GET', '/api/teacher/materials'],
            ['POST', '/api/teacher/materials'],
        ];

        foreach ($routes as [$method, $uri]) {
            $res = $method === 'GET' ? $this->getJson($uri) : $this->postJson($uri, []);
            $this->assertEquals(403, $res->status(), "Route {$method} {$uri} expected 403 for student");
        }
    }
}
