<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\Package;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\Timetable;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Models\ZoomSchedule;
use App\Models\AdvancePaymentRequest;
use App\Models\MediumChangeRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Institute $inst1;
    private Institute $inst2;
    private User $admin1;
    private User $admin2;
    private User $student1;
    private User $student2;
    private User $studentPending;
    private User $studentDeactivated;
    private User $studentChild1;
    private User $studentChild2;
    private User $parent;
    private User $teacher;
    private Subject $subject1;
    private Subject $subject2;
    private Package $package1;
    private ZoomAccount $zoomAccount;
    private Timetable $timetable;
    private ZoomSchedule $zoomSchedule;
    private Payment $paymentPaid;
    private Payment $paymentTopupPaid;
    private Payment $paymentPending;
    private Payment $paymentTopupPending;
    private SubjectTopup $subjectTopup;
    private AdvancePaymentRequest $advanceRequest;
    private MediumChangeRequest $mediumRequest;
    private Attendance $attendance;
    private ExamResult $examResult;
    private Notification $notification;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        // 1. Institutes
        $this->inst1 = Institute::create([
            'id' => 1,
            'name' => 'TiT Jaffna Campus',
            'slug' => 'tit-jaffna',
            'domain' => 'jaffna.tit.lk',
            'status' => 'active',
            'index_prefix' => 'TIT',
            'index_format' => 'series',
        ]);

        $this->inst2 = Institute::create([
            'id' => 2,
            'name' => 'Foreign Institute',
            'slug' => 'foreign-inst',
            'domain' => 'foreign.tit.lk',
            'status' => 'active',
            'index_prefix' => 'FOR',
            'index_format' => 'series',
        ]);

        // 2. Admins
        $this->admin1 = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->inst1->id,
            'email' => 'admin1@tit.lk',
        ]);

        $this->admin2 = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->inst2->id,
            'email' => 'admin2@foreign.lk',
        ]);

        // 3. Parent and Children (Students)
        $this->parent = User::factory()->create([
            'role' => 'parent',
            'institute_id' => $this->inst1->id,
            'email' => 'parent@tit.lk',
            'phone_number' => '+94771234567',
        ]);

        $this->studentChild1 = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->inst1->id,
            'parent_id' => $this->parent->id,
            'full_name' => 'Child Student One',
            'name' => 'Child One',
            'email' => 'child1@tit.lk',
            'current_grade' => '10',
            'medium' => 'english',
            'admin_confirmed_at' => now(),
            'registration_status' => 'approved',
        ]);

        $this->studentChild2 = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->inst1->id,
            'parent_id' => $this->parent->id,
            'full_name' => 'Child Student Two',
            'name' => 'Child Two',
            'email' => 'child2@tit.lk',
            'current_grade' => '11',
            'medium' => 'tamil',
            'admin_confirmed_at' => now(),
            'registration_status' => 'approved',
        ]);

        // 4. Other Students (confirmed, pending, deactivated)
        $this->student1 = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->inst1->id,
            'full_name' => 'Active Student Twelve',
            'name' => 'Student 12',
            'email' => 'student12@tit.lk',
            'current_grade' => '12',
            'stream' => 'bio_maths',
            'admin_confirmed_at' => now(),
            'registration_status' => 'approved',
            'index_number' => 'TIT/26/001',
            'batch_year' => 26,
            'index_seq' => 1,
        ]);

        $this->student2 = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->inst1->id,
            'full_name' => 'Active Student Thirteen',
            'name' => 'Student 13',
            'email' => 'student13@tit.lk',
            'current_grade' => '13',
            'stream' => 'arts',
            'admin_confirmed_at' => now(),
            'registration_status' => 'approved',
            'index_number' => 'TIT/25/001',
            'batch_year' => 25,
            'index_seq' => 1,
        ]);

        $this->studentPending = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->inst1->id,
            'full_name' => 'Pending Student',
            'name' => 'Pending',
            'email' => 'pending@tit.lk',
            'current_grade' => '12',
            'admin_confirmed_at' => null,
            'registration_status' => 'pending_approval',
        ]);

        $this->studentDeactivated = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->inst1->id,
            'full_name' => 'Deactivated Student',
            'name' => 'Deactivated',
            'email' => 'deact@tit.lk',
            'current_grade' => '12',
            'admin_confirmed_at' => now(),
            'registration_status' => 'approved',
            'deactivated_at' => now(),
        ]);

        // Student for Institute 2
        User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->inst2->id,
            'full_name' => 'Inst 2 Student',
            'name' => 'Inst 2 Student',
            'email' => 'student@foreign.lk',
            'current_grade' => '12',
            'admin_confirmed_at' => now(),
            'registration_status' => 'approved',
        ]);

        // 5. Teachers
        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->inst1->id,
            'name' => 'Teacher Venthan',
            'email' => 'teacher@tit.lk',
        ]);

        User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->inst2->id,
            'name' => 'Foreign Teacher',
            'email' => 'teacher@foreign.lk',
        ]);

        // 6. Subjects and Packages
        $this->subject1 = Subject::create([
            'name' => 'Pure Mathematics',
            'price' => 2000,
            'category' => 'al',
            'medium' => 'english',
        ]);

        $this->subject2 = Subject::create([
            'name' => 'Combined Mathematics',
            'price' => 2500,
            'category' => 'al',
            'medium' => 'english',
        ]);

        $this->package1 = Package::create([
            'name' => 'AL Science Full Pack',
            'category' => 'al',
            'medium' => 'english',
            'package_price' => 5000,
            'original_price' => 5500,
            'type' => 'fixed',
            'applicable_grades' => ['12'],
            'is_active' => true,
        ]);

        // 7. Zoom & Timetables
        $this->zoomAccount = ZoomAccount::create([
            'institute_id' => $this->inst1->id,
            'email' => 'zoom@tit.lk',
            'account_id' => 'zm_acc_1',
            'client_id' => 'zm_client_1',
            'client_secret' => 'zm_secret_1',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::create([
            'institute_id' => $this->inst1->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject1->id,
            'title' => 'Grade 12 Pure Maths Live',
            'day_of_week' => 'Monday',
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'grade' => '12',
            'medium' => 'english',
            'is_active' => true,
        ]);

        $this->zoomSchedule = ZoomSchedule::create([
            'institute_id' => $this->inst1->id,
            'timetable_id' => $this->timetable->id,
            'title' => 'Grade 12 Pure Maths Live Session',
            'scheduled_at' => now()->startOfDay()->addHours(16),
            'zoom_link' => 'https://zoom.us/j/123456789',
            'meeting_id' => '123456789',
        ]);

        // 8. Learning Material
        LearningMaterial::create([
            'institute_id' => $this->inst1->id,
            'title' => 'Calculus Note 01',
            'type' => 'note',
            'grade' => '12',
            'subject_id' => $this->subject1->id,
            'file_path' => 'notes/calc01.pdf',
        ]);

        // 9. Payments (various kinds and statuses)
        $ym = now()->format('Y-m');
        $this->paymentPaid = Payment::create([
            'institute_id' => $this->inst1->id,
            'user_id' => $this->student1->id,
            'year_month' => $ym,
            'amount' => 2000,
            'kind' => 'monthly',
            'status' => 'paid',
            'payment_method' => 'manual',
            'paid_at' => now(),
        ]);

        $this->paymentTopupPaid = Payment::create([
            'institute_id' => $this->inst1->id,
            'user_id' => $this->student1->id,
            'year_month' => $ym,
            'amount' => 500,
            'kind' => 'topup',
            'status' => 'paid',
            'payment_method' => 'manual',
            'paid_at' => now(),
        ]);

        $this->paymentPending = Payment::create([
            'institute_id' => $this->inst1->id,
            'user_id' => $this->student2->id,
            'year_month' => $ym,
            'amount' => 2000,
            'kind' => 'monthly',
            'status' => 'pending',
            'payment_method' => 'manual',
        ]);

        $this->paymentTopupPending = Payment::create([
            'institute_id' => $this->inst1->id,
            'user_id' => $this->studentChild1->id,
            'year_month' => $ym,
            'amount' => 600,
            'kind' => 'topup',
            'status' => 'pending',
            'payment_method' => 'manual',
        ]);

        // Payment in Institute 2 (to verify revenue and pending scoping)
        Payment::create([
            'institute_id' => $this->inst2->id,
            'user_id' => 9999,
            'year_month' => $ym,
            'amount' => 9999,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // 10. Subject Top-up Row
        $this->subjectTopup = SubjectTopup::create([
            'institute_id' => $this->inst1->id,
            'user_id' => $this->studentChild1->id,
            'year_month' => $ym,
            'added_subjects' => ['Combined Mathematics'],
            'previous_subjects' => ['Pure Mathematics'],
            'amount' => 600,
            'status' => 'pending',
        ]);

        // 11. Advance & Medium Requests
        $this->advanceRequest = AdvancePaymentRequest::create([
            'user_id' => $this->student1->id,
            'months_count' => 3,
            'amount' => 6000,
            'status' => 'pending',
        ]);

        $this->mediumRequest = MediumChangeRequest::create([
            'user_id' => $this->studentChild1->id,
            'current_medium' => 'english',
            'requested_medium' => 'tamil',
            'status' => 'pending',
        ]);

        // 12. Attendance, Exam Results
        $this->attendance = Attendance::create([
            'user_id' => $this->student1->id,
            'zoom_schedule_id' => $this->zoomSchedule->id,
            'role' => 'student',
            'status' => 'present',
            'marked_at' => now(),
            'source' => 'manual',
        ]);

        $this->examResult = ExamResult::create([
            'institute_id' => $this->inst1->id,
            'student_name' => $this->student1->name,
            'index_no' => $this->student1->index_number,
            'term' => '1st Term',
            'grade' => '12',
            'subject' => 'Pure Mathematics',
            'marks' => 88.0,
            'year' => 2026,
        ]);
    }

    /**
     * (a) GET crawl: Every admin route without parameters AND every route with {id} parameters
     * status must be 200/302, never 500.
     */
    public function test_admin_smoke_crawl_all_get_routes(): void
    {
        $this->actingAs($this->admin1, 'web');

        $routesToTest = [
            // Parameter-less routes
            ['GET', '/admin', 302],
            ['GET', '/admin/dashboard', 200],
            ['GET', '/admin/students', 200],
            ['GET', '/admin/students/pending-payments', 200],
            ['GET', '/admin/students/create', 200],
            ['GET', '/admin/students/index-lists', 200],
            ['GET', '/admin/students/assign-index-tool', 200],
            ['GET', '/admin/students/import-index', 200],
            ['GET', '/admin/students/import-index/template', 200],
            ['GET', '/admin/students-requests/medium-changes', 200],
            ['GET', '/admin/students-requests/advance-payments', 200],
            ['GET', '/admin/teachers', 200],
            ['GET', '/admin/teachers/create', 200],
            ['GET', '/admin/subjects', 200],
            ['GET', '/admin/packages', 200],
            ['GET', '/admin/settings', 200],
            ['GET', '/admin/settings/about', 200],
            ['GET', '/admin/settings/contact', 200],
            ['GET', '/admin/settings/learning', 200],
            ['GET', '/admin/settings/classes', 200],
            ['GET', '/admin/settings/footer', 200],
            ['GET', '/admin/settings/register', 200],
            ['GET', '/admin/settings/admission-fees', 200],
            ['GET', '/admin/settings/payments', 200],
            ['GET', '/admin/settings/grade-promotion-schedule', 200],
            ['GET', '/admin/settings/index-batches', 200],
            ['GET', '/admin/settings/index-series', 200],
            ['GET', '/admin/firebase-token', 200],
            ['GET', '/admin/zoom', 200],
            ['GET', '/admin/zoom/create', 200],
            ['GET', '/admin/zoom-accounts', 200],
            ['GET', '/admin/zoom-accounts/create', 200],
            ['GET', '/admin/timetables', 200],
            ['GET', '/admin/timetables/create', 200],
            ['GET', '/admin/timetables/sync', 302],
            ['GET', '/admin/attendance', 200],
            ['GET', '/admin/messages', 200],
            ['GET', '/admin/messages/unread-count', 200],
            ['GET', '/admin/profile', 200],
            ['GET', '/admin/profile/settings', 200],
            ['GET', '/admin/calendar/events', 200],
            ['GET', '/admin/calendar/events/counts', 200],
            ['GET', '/admin/notifications', 200],
            ['GET', '/admin/exam-results', 200],
            ['GET', '/admin/exam-results/data', 200],
            ['GET', '/admin/search?q=Student', 200],

            // Parameterized routes using seeded IDs
            ['GET', '/admin/students/' . $this->student1->id, 200],
            ['GET', '/admin/students/' . $this->student1->id . '/edit', 200],
            ['GET', '/admin/teachers/' . $this->teacher->id . '/edit', 200],
            ['GET', '/admin/zoom/' . $this->zoomSchedule->id . '/edit', 200],
            ['GET', '/admin/zoom-accounts/' . $this->zoomAccount->id . '/edit', 200],
            ['GET', '/admin/timetables/' . $this->timetable->id . '/edit', 200],
        ];

        foreach ($routesToTest as [$method, $uri, $expectedStatus]) {
            $response = $this->call($method, $uri);
            $status = $response->getStatusCode();
            $this->assertTrue(
                in_array($status, [$expectedStatus, 200, 302], true),
                "Route {$method} {$uri} returned unexpected HTTP {$status} (expected {$expectedStatus})."
            );
            $this->assertNotEquals(500, $status, "Route {$method} {$uri} caused a 500 error.");
        }
    }

    /**
     * (b) Action tests: Execute key forms with valid input AND invalid input
     * Valid -> 200/302, Invalid -> validation error (302/422), never 500.
     */
    public function test_admin_actions_valid_and_invalid_inputs(): void
    {
        $this->actingAs($this->admin1, 'web');

        // 1. Confirm student (valid)
        $resp = $this->post('/admin/students/' . $this->studentPending->id . '/confirm');
        $this->assertTrue(in_array($resp->getStatusCode(), [200, 302]));
        $this->assertNotNull($this->studentPending->fresh()->admin_confirmed_at);

        // 2. Reject student
        $resp = $this->post('/admin/students/' . $this->studentPending->id . '/reject');
        $this->assertTrue(in_array($resp->getStatusCode(), [200, 302]));

        // 3. Deactivate & Activate student
        $resp = $this->post('/admin/students/' . $this->student1->id . '/deactivate');
        $this->assertTrue(in_array($resp->getStatusCode(), [200, 302]));
        $this->assertNotNull($this->student1->fresh()->deactivated_at);

        $resp = $this->post('/admin/students/' . $this->student1->id . '/activate');
        $this->assertTrue(in_array($resp->getStatusCode(), [200, 302]));
        $this->assertNull($this->student1->fresh()->deactivated_at);

        // 4. Mark Payment Paid (valid)
        $resp = $this->post('/admin/students/' . $this->student2->id . '/payments/' . $this->paymentPending->id . '/mark-paid', [
            'payment_method' => 'cash',
            'received_by' => 'cash',
        ]);
        $this->assertTrue(in_array($resp->getStatusCode(), [200, 302]));
        $this->assertEquals('paid', $this->paymentPending->fresh()->status);

        // 5. Revert Payment (valid)
        $resp = $this->post('/admin/students/' . $this->student2->id . '/payments/' . $this->paymentPending->id . '/revert');
        $this->assertTrue(in_array($resp->getStatusCode(), [200, 302]));
        $this->assertEquals('pending', $this->paymentPending->fresh()->status);

        // 6. Package creation (valid vs invalid)
        // Invalid (empty)
        $respInvalid = $this->post('/admin/packages', []);
        $this->assertTrue(in_array($respInvalid->getStatusCode(), [302, 422]));
        $this->assertNotEquals(500, $respInvalid->getStatusCode());

        // Mock translator service so it doesn't try to call external Gemini API in test
        $mockTranslator = \Mockery::mock(\App\Services\GeminiTranslationService::class);
        $mockTranslator->shouldReceive('translate')->andReturn('Tamil Name', 'Sinhala Name');
        $this->app->instance(\App\Services\GeminiTranslationService::class, $mockTranslator);

        $respValid = $this->post('/admin/packages', [
            'name' => 'O/L Complete Pack',
            'category' => 'ol',
            'medium' => 'english',
            'package_price' => 4500,
            'original_price' => 5000,
            'type' => 'fixed',
            'applicable_grades' => ['10'],
            'is_active' => '1',
        ]);
        $this->assertTrue(in_array($respValid->getStatusCode(), [200, 302]));
        $this->assertDatabaseHas('packages', ['name' => 'O/L Complete Pack']);

        // 7. Timetable toggle status
        $resp = $this->post('/admin/timetables/' . $this->timetable->id . '/toggle');
        $this->assertTrue(in_array($resp->getStatusCode(), [200, 302]));

        // 8. Notifications mark as read
        $resp = $this->post('/admin/notifications/mark-all-as-read');
        $this->assertTrue(in_array($resp->getStatusCode(), [200, 302]));
    }

    /**
     * (c) Dashboard logic audit: compare counters with independent DB queries
     */
    public function test_dashboard_counters_match_db_and_scoping(): void
    {
        $this->actingAs($this->admin1, 'web');
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);

        // 1. Total Students: User where role=user, full_name is not null, institute_id=1
        $expectedStudents = User::where('role', 'user')
            ->whereNotNull('full_name')
            ->where('institute_id', $this->inst1->id)
            ->count();
        $response->assertViewHas('totalStudents', $expectedStudents);

        // 2. Total Teachers: User where role=teacher, institute_id=1
        $expectedTeachers = User::where('role', 'teacher')
            ->where('institute_id', $this->inst1->id)
            ->count();
        $response->assertViewHas('totalTeachers', $expectedTeachers);

        // 3. Total Courses / Learning Materials: institute_id=1
        $expectedCourses = LearningMaterial::where('institute_id', $this->inst1->id)->count();
        $response->assertViewHas('totalCourses', $expectedCourses);

        // 4. Active Classes Today: ZoomSchedule today, institute_id=1
        $expectedActiveToday = ZoomSchedule::where('institute_id', $this->inst1->id)
            ->whereDate('scheduled_at', now()->toDateString())
            ->count();
        $response->assertViewHas('activeClassesToday', $expectedActiveToday);

        // 5. Total Revenue: Only PAID payments, institute_id=1
        $expectedRevenue = (float) Payment::where('institute_id', $this->inst1->id)
            ->where('status', 'paid')
            ->sum('amount');
        $response->assertViewHas('totalRevenue', $expectedRevenue);

        // 6. Pending Payments (Monthly vs Top-ups)
        $ym = now()->format('Y-m');
        $expectedPending = Payment::where('institute_id', $this->inst1->id)
            ->where('status', 'pending')
            ->where('year_month', $ym)
            ->count();
        $expectedPendingTopups = Payment::where('institute_id', $this->inst1->id)
            ->where('status', 'pending')
            ->where('year_month', $ym)
            ->where('kind', 'topup')
            ->count();
        $expectedPendingMonthly = $expectedPending - $expectedPendingTopups;

        $response->assertViewHas('pendingPayments', $expectedPending);
        $response->assertViewHas('pendingTopups', $expectedPendingTopups);
        $response->assertViewHas('pendingMonthly', $expectedPendingMonthly);
    }

    /**
     * (d) Tenant Isolation: Admin 2 (Foreign Institute) cannot see or mutate Admin 1's data
     */
    public function test_tenant_isolation_prevents_cross_institute_leakage(): void
    {
        // Admin 2 opens dashboard
        $this->actingAs($this->admin2, 'web');
        $resp = $this->get('/admin/dashboard');
        $resp->assertStatus(200);

        // Admin 2 should only see Inst 2 student count (1 student)
        $resp->assertViewHas('totalStudents', 1);

        // Admin 2 should only see Inst 2 revenue (LKR 9999.00), NOT Inst 1 revenue (LKR 2500)
        $resp->assertViewHas('totalRevenue', 9999.0);

        // Admin 2 should not see Inst 1 students in list
        $respStudents = $this->get('/admin/students');
        $respStudents->assertStatus(200);
        $respStudents->assertDontSee($this->student1->full_name);
        $respStudents->assertDontSee($this->studentChild1->full_name);
    }

    /**
     * (e) Authorisation: student, teacher, and parent sessions cannot access admin routes
     */
    public function test_authorization_blocks_student_teacher_parent_from_all_admin_routes(): void
    {
        $nonAdminUsers = [
            'student' => $this->student1,
            'teacher' => $this->teacher,
            'parent' => $this->parent,
        ];

        $protectedRoutes = [
            '/admin/dashboard',
            '/admin/students',
            '/admin/teachers',
            '/admin/settings',
            '/admin/settings/admission-fees',
            '/admin/zoom',
            '/admin/timetables',
            '/admin/exam-results',
        ];

        foreach ($nonAdminUsers as $role => $user) {
            $this->actingAs($user, 'web');
            foreach ($protectedRoutes as $route) {
                $response = $this->get($route);
                $status = $response->getStatusCode();
                $this->assertTrue(
                    in_array($status, [302, 403], true),
                    "Role {$role} accessed protected route {$route} with unexpected status {$status}."
                );
                if ($status === 302) {
                    $response->assertRedirect('/admin/login');
                }
            }
        }
    }
}

