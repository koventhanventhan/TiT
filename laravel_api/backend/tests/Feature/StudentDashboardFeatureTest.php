<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\Payment;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Models\ZoomSchedule;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $student;
    private User $teacher;

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

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute->id,
            'name' => 'Teacher Alpha',
            'email' => 'teacher.alpha@example.com',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Student Alex',
            'email' => 'alex@student.com',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 4',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Maths']),
        ]);

        // Mark monthly payment as paid
        Payment::create([
            'user_id' => $this->student->id,
            'year_month' => now()->format('Y-m'),
            'status' => 'paid',
            'amount' => 1500,
            'paid_at' => now(),
            'payment_type' => 'monthly',
            'institute_id' => $this->institute->id,
        ]);
    }

    public function test_student_schedule_endpoints_do_not_leak_zoom_host_link_or_private_data(): void
    {
        Sanctum::actingAs($this->student);

        $zoomAccount = ZoomAccount::create([
            'email' => 'zoom@test.com',
            'account_id' => 'acc_888',
            'client_id' => 'cid_888',
            'client_secret' => 'sec_888',
            'institute_id' => $this->institute->id,
        ]);

        $schedule = ZoomSchedule::create([
            'title' => 'Maths Live Class',
            'scheduled_at' => now()->addHours(2),
            'duration' => 60,
            'grade' => 'Grade 4',
            'subject' => 'Maths',
            'medium' => 'english',
            'zoom_link' => 'https://zoom.us/j/123456789',
            'join_url' => 'https://zoom.us/j/123456789?pwd=xyz',
            'start_url' => 'https://zoom.us/s/123456789?zak=super_secret_host_zak_token',
            'meeting_id' => '123456789',
            'password' => 'pass123',
            'zoom_account_id' => $zoomAccount->id,
            'created_by' => $this->teacher->id,
            'institute_id' => $this->institute->id,
        ]);
        $schedule->teachers()->attach($this->teacher->id);

        // 1. Upcoming schedules
        $upcomingResp = $this->getJson('/api/student/upcoming-schedules');
        $upcomingResp->assertStatus(200);
        $upcomingJson = $upcomingResp->getContent();

        $this->assertStringNotContainsString('start_url', $upcomingJson);
        $this->assertStringNotContainsString('zak=', $upcomingJson);
        $this->assertStringNotContainsString('zoom_account_id', $upcomingJson);
        $this->assertStringNotContainsString('teacher.alpha@example.com', $upcomingJson);
        $this->assertStringContainsString('Maths Live Class', $upcomingJson);
        $this->assertStringContainsString('Teacher Alpha', $upcomingJson);

        // 2. Zoom classes
        $classesResp = $this->getJson('/api/student/zoom-classes');
        $classesResp->assertStatus(200);
        $classesJson = $classesResp->getContent();

        $this->assertStringNotContainsString('start_url', $classesJson);
        $this->assertStringNotContainsString('zak=', $classesJson);
        $this->assertStringNotContainsString('zoom_account_id', $classesJson);
        $this->assertStringNotContainsString('teacher.alpha@example.com', $classesJson);
        $this->assertStringContainsString('Maths Live Class', $classesJson);
    }

    public function test_student_schedule_visibility_filters_by_grade_medium_and_subject(): void
    {
        Sanctum::actingAs($this->student);

        // Visible 1: Grade 4, english, Maths
        $sched1 = ZoomSchedule::create([
            'title' => 'Maths English G4 A',
            'zoom_link' => 'https://zoom.us/j/111',
            'scheduled_at' => now()->addHours(1),
            'duration' => 60,
            'grade' => 'Grade 4',
            'subject' => 'Maths',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
        ]);
        $sched1->teachers()->attach($this->teacher->id);

        // Visible 2: Grade 4, english, Maths B
        $sched2 = ZoomSchedule::create([
            'title' => 'Maths English G4 B',
            'zoom_link' => 'https://zoom.us/j/222',
            'scheduled_at' => now()->addHours(2),
            'duration' => 60,
            'grade' => 'Grade 4',
            'subject' => 'Maths',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
        ]);
        $sched2->teachers()->attach($this->teacher->id);

        // Hidden: Grade 5 (different grade)
        $sched3 = ZoomSchedule::create([
            'title' => 'Maths G5',
            'zoom_link' => 'https://zoom.us/j/333',
            'scheduled_at' => now()->addHours(3),
            'duration' => 60,
            'grade' => 'Grade 5',
            'subject' => 'Maths',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
        ]);
        $sched3->teachers()->attach($this->teacher->id);

        // Hidden: Tamil medium
        $sched4 = ZoomSchedule::create([
            'title' => 'Maths Tamil G4',
            'zoom_link' => 'https://zoom.us/j/444',
            'scheduled_at' => now()->addHours(4),
            'duration' => 60,
            'grade' => 'Grade 4',
            'subject' => 'Maths',
            'medium' => 'tamil',
            'institute_id' => $this->institute->id,
        ]);
        $sched4->teachers()->attach($this->teacher->id);

        // Hidden: Science (subject not in student's selected_subjects ['Maths'])
        $sched5 = ZoomSchedule::create([
            'title' => 'Science English G4',
            'zoom_link' => 'https://zoom.us/j/555',
            'scheduled_at' => now()->addHours(5),
            'duration' => 60,
            'grade' => 'Grade 4',
            'subject' => 'Science',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
        ]);
        $sched5->teachers()->attach($this->teacher->id);

        // Check index
        $indexResp = $this->getJson('/api/student/zoom-classes');
        $indexResp->assertStatus(200);
        $this->assertCount(2, $indexResp->json('data'));

        // Check upcomingSchedules
        $upcomingResp = $this->getJson('/api/student/upcoming-schedules');
        $upcomingResp->assertStatus(200);
        $this->assertCount(2, $upcomingResp->json());

        // Check stats
        $statsResp = $this->getJson('/api/student/stats');
        $statsResp->assertStatus(200);
        $this->assertEquals(2, $statsResp->json('today_classes'));
        $this->assertEquals(2, $statsResp->json('upcoming_classes'));
    }

    public function test_stats_assignment_counts_use_grade_matcher(): void
    {
        Sanctum::actingAs($this->student);

        // Assignment 1: grade "4" (should match student "Grade 4" via GradeMatcher)
        Assignment::create([
            'title' => 'Geometry Basics',
            'subject' => 'Maths',
            'grade' => '4',
            'due_date' => now()->addDays(5),
            'teacher_id' => $this->teacher->id,
            'institute_id' => $this->institute->id,
        ]);

        // Assignment 2: grade "Grade 5" (should NOT match)
        Assignment::create([
            'title' => 'Grade 5 Algebra',
            'subject' => 'Maths',
            'grade' => 'Grade 5',
            'due_date' => now()->addDays(5),
            'teacher_id' => $this->teacher->id,
            'institute_id' => $this->institute->id,
        ]);

        // Assignment 3: grade empty (visible to all)
        $generalAssign = Assignment::create([
            'title' => 'General Quiz',
            'subject' => 'General',
            'grade' => null,
            'due_date' => now()->addDays(5),
            'teacher_id' => $this->teacher->id,
            'institute_id' => $this->institute->id,
        ]);

        $statsResp = $this->getJson('/api/student/stats');
        $statsResp->assertStatus(200);
        $this->assertEquals(2, $statsResp->json('assignments_total'));
        $this->assertEquals(2, $statsResp->json('pending_assignments'));

        // Student submits general quiz
        AssignmentSubmission::create([
            'assignment_id' => $generalAssign->id,
            'student_id' => $this->student->id,
            'file_path' => 'submissions/sample.pdf',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $statsResp2 = $this->getJson('/api/student/stats');
        $statsResp2->assertStatus(200);
        $this->assertEquals(2, $statsResp2->json('assignments_total'));
        $this->assertEquals(1, $statsResp2->json('pending_assignments'));
    }

    public function test_stats_attendance_rate_and_study_hours_calculation(): void
    {
        Sanctum::actingAs($this->student);

        // 1. Initial state with no held classes
        $initialStats = $this->getJson('/api/student/stats');
        $initialStats->assertStatus(200)
            ->assertJson([
                'attendance_rate' => 0,
                'attendance_rate_has_data' => false,
                'attended_classes' => 0,
                'held_classes' => 0,
                'study_hours' => 0,
            ]);

        // 2. Create 4 held classes in last 30 days visible to student (Grade 4, english, Maths)
        // 90 minutes each
        $endedSchedules = [];
        for ($i = 1; $i <= 4; $i++) {
            $s = ZoomSchedule::create([
                'title' => "Held Class {$i}",
                'zoom_link' => "https://zoom.us/j/held_{$i}",
                'scheduled_at' => now()->subDays(5)->addHours($i * 2),
                'duration' => 90,
                'grade' => 'Grade 4',
                'subject' => 'Maths',
                'medium' => 'english',
                'institute_id' => $this->institute->id,
            ]);
            $endedSchedules[] = $s;
        }

        // Add 1 class of other grade (Grade 10) in last 30 days (should NOT be counted in held_classes)
        ZoomSchedule::create([
            'title' => 'Other Grade Class',
            'zoom_link' => 'https://zoom.us/j/other_grade',
            'scheduled_at' => now()->subDays(5),
            'duration' => 60,
            'grade' => 'Grade 10',
            'subject' => 'Maths',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
        ]);

        // Student attended 3 of the 4 held classes
        for ($i = 0; $i < 3; $i++) {
            Attendance::create([
                'zoom_schedule_id' => $endedSchedules[$i]->id,
                'user_id' => $this->student->id,
                'role' => 'student',
                'status' => 'present',
                'marked_at' => now()->subDays(5),
                'source' => 'link_click',
            ]);
        }

        $statsResp = $this->getJson('/api/student/stats');
        $statsResp->assertStatus(200);

        // 3 out of 4 = 75%
        $this->assertEquals(75, $statsResp->json('attendance_rate'));
        $this->assertTrue($statsResp->json('attendance_rate_has_data'));
        $this->assertEquals(3, $statsResp->json('attended_classes'));
        $this->assertEquals(4, $statsResp->json('held_classes'));

        // Study hours = 3 classes * 90 min = 270 min / 60 = 4.5 hours
        $this->assertEquals(4.5, $statsResp->json('study_hours'));
    }

    public function test_student_attend_authorization_and_notification_deduplication(): void
    {
        Sanctum::actingAs($this->student);

        $visibleSchedule = ZoomSchedule::create([
            'title' => 'Visible Class Today',
            'zoom_link' => 'https://zoom.us/j/vis_1',
            'scheduled_at' => now()->addMinutes(10),
            'duration' => 60,
            'grade' => 'Grade 4',
            'subject' => 'Maths',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
        ]);

        $invisibleSchedule = ZoomSchedule::create([
            'title' => 'Grade 10 Class',
            'zoom_link' => 'https://zoom.us/j/invis_1',
            'scheduled_at' => now()->addMinutes(10),
            'duration' => 60,
            'grade' => 'Grade 10',
            'subject' => 'Maths',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
        ]);

        // Invisible class -> 403
        $respForbidden = $this->postJson('/api/student/attend', [
            'zoom_schedule_id' => $invisibleSchedule->id,
        ]);
        $respForbidden->assertStatus(403);

        // Mock NotificationService to test deduplication
        $mockNotifier = $this->mock(NotificationService::class);
        $mockNotifier->shouldReceive('notifyUser')
            ->once()
            ->andReturn(true);

        // 1st click on visible class -> 200, row created, notification sent
        $resp1 = $this->postJson('/api/student/attend', [
            'zoom_schedule_id' => $visibleSchedule->id,
        ]);
        $resp1->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'zoom_schedule_id' => $visibleSchedule->id,
            'user_id' => $this->student->id,
            'role' => 'student',
            'status' => 'present',
        ]);

        // 2nd click on visible class -> 200, but no 2nd notification
        $resp2 = $this->postJson('/api/student/attend', [
            'zoom_schedule_id' => $visibleSchedule->id,
        ]);
        $resp2->assertStatus(200);
    }

    public function test_submit_assignment_hardening_and_teacher_material_hardening(): void
    {
        Storage::fake('public');

        Sanctum::actingAs($this->student);

        $assignment = Assignment::create([
            'title' => 'Maths Homework 1',
            'subject' => 'Maths',
            'grade' => 'Grade 4',
            'due_date' => now()->addDays(3),
            'teacher_id' => $this->teacher->id,
            'institute_id' => $this->institute->id,
        ]);

        $otherGradeAssignment = Assignment::create([
            'title' => 'Grade 11 Physics',
            'subject' => 'Physics',
            'grade' => 'Grade 11',
            'due_date' => now()->addDays(3),
            'teacher_id' => $this->teacher->id,
            'institute_id' => $this->institute->id,
        ]);

        // 1. Submit .php file -> rejected 422
        $phpFile = UploadedFile::fake()->create('malicious.php', 10, 'application/x-php');
        $respPhp = $this->postJson("/api/student/assignments/{$assignment->id}/submit", [
            'file' => $phpFile,
        ]);
        $respPhp->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // 2. Submit .html file -> rejected 422
        $htmlFile = UploadedFile::fake()->create('page.html', 10, 'text/html');
        $respHtml = $this->postJson("/api/student/assignments/{$assignment->id}/submit", [
            'file' => $htmlFile,
        ]);
        $respHtml->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // 3. Submit for other grade assignment -> 403
        $pdfFile = UploadedFile::fake()->create('valid.pdf', 100, 'application/pdf');
        $respForbidden = $this->postJson("/api/student/assignments/{$otherGradeAssignment->id}/submit", [
            'file' => $pdfFile,
        ]);
        $respForbidden->assertStatus(403);

        // 4. Submit valid pdf file -> 200, status pending, submitted_at set, file_url appended
        $validPdf = UploadedFile::fake()->create('homework.pdf', 100, 'application/pdf');
        $respValid = $this->postJson("/api/student/assignments/{$assignment->id}/submit", [
            'file' => $validPdf,
        ]);
        $respValid->assertStatus(200);
        $this->assertNotNull($respValid->json('file_url'));
        $this->assertEquals('pending', $respValid->json('status'));
        $this->assertNotNull($respValid->json('submitted_at'));

        // 5. Already graded assignment cannot be resubmitted -> 422
        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $this->student->id)
            ->first();
        $submission->update(['status' => 'graded', 'marks' => 85]);

        $resubmitFile = UploadedFile::fake()->create('homework_v2.pdf', 100, 'application/pdf');
        $respGraded = $this->postJson("/api/student/assignments/{$assignment->id}/submit", [
            'file' => $resubmitFile,
        ]);
        $respGraded->assertStatus(422)
            ->assertJson(['message' => 'Already graded, resubmission is not allowed']);

        // 6. Teacher material store hardening
        Sanctum::actingAs($this->teacher);

        $teacherPhp = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');
        $respTeacherPhp = $this->postJson('/api/teacher/materials', [
            'title' => 'Invalid Material',
            'type' => 'note',
            'file' => $teacherPhp,
        ]);
        $respTeacherPhp->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $teacherHtml = UploadedFile::fake()->create('index.html', 10, 'text/html');
        $respTeacherHtml = $this->postJson('/api/teacher/materials', [
            'title' => 'Invalid HTML Material',
            'type' => 'note',
            'file' => $teacherHtml,
        ]);
        $respTeacherHtml->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $teacherPdf = UploadedFile::fake()->create('lesson.pdf', 100, 'application/pdf');
        $respTeacherPdf = $this->postJson('/api/teacher/materials', [
            'title' => 'Valid Lesson PDF',
            'type' => 'note',
            'file' => $teacherPdf,
        ]);
        $respTeacherPdf->assertStatus(201);
    }
}
