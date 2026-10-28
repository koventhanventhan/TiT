<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudentFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit-institute',
            'index_format' => 'grade',
            'index_prefix' => 'TIT',
            'index_seq_start' => 1,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => 1,
        ]);
    }

    public function test_stray_batch_year_is_ignored_in_grade_mode(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'name' => 'grade8_user',
            'full_name' => 'Grade 8 Student',
            'current_grade' => 'Grade 8',
            'batch_year' => 8,
            'medium' => 'english',
        ]);

        // Request with stray batch_year=28 in grade mode should NOT filter out this student
        $response = $this->actingAs($this->admin)->get(route('admin.students.index', [
            'batch_year' => 28,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Grade 8 Student');
        $response->assertDontSee('All Batches'); // batch dropdown not rendered in grade mode
    }

    public function test_medium_filter_filters_students_correctly(): void
    {
        $engStudent = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'name' => 'eng_user',
            'full_name' => 'English Medium Student',
            'current_grade' => 'Grade 8',
            'medium' => 'english',
        ]);

        $tamStudent = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'name' => 'tam_user',
            'full_name' => 'Tamil Medium Student',
            'current_grade' => 'Grade 8',
            'medium' => 'tamil',
        ]);

        // Filter English
        $responseEng = $this->actingAs($this->admin)->get(route('admin.students.index', [
            'medium' => 'english',
        ]));
        $responseEng->assertStatus(200);
        $responseEng->assertSee('English Medium Student');
        $responseEng->assertDontSee('Tamil Medium Student');

        // Filter Tamil
        $responseTam = $this->actingAs($this->admin)->get(route('admin.students.index', [
            'medium' => 'tamil',
        ]));
        $responseTam->assertStatus(200);
        $responseTam->assertSee('Tamil Medium Student');
        $responseTam->assertDontSee('English Medium Student');
    }

    public function test_header_strip_renders_tooltips_and_relative_pending_link(): void
    {
        $studentWithPending = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'name' => 'pending_user',
            'full_name' => 'Pending Offline Student',
            'current_grade' => 'Grade 8',
            'index_number' => 'TIT/08/001',
        ]);

        Payment::create([
            'user_id' => $studentWithPending->id,
            'institute_id' => 1,
            'amount' => 3000,
            'status' => 'pending',
            'payment_method' => 'offline',
            'year_month' => now()->format('Y-m'),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.index'));
        $response->assertStatus(200);

        // Strip text and tooltips
        $response->assertSee('students have an index number');
        $response->assertSee('Pending Payments: 1');
        $response->assertSee('Strip Legend:');

        // Verify pending link is relative and points to dedicated pending-payments page
        $content = $response->getContent();
        $this->assertStringNotContainsString('/laravel_api/backend/public/admin/students/pending-payments', $content);
        $this->assertStringContainsString('/admin/students/pending-payments', $content);
    }
}
