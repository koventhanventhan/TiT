<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudentPaymentRunningTotalTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $admin;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'email' => 'admin@tit.lk',
        ]);

        Subject::create([
            'name' => 'Mathematics',
            'price' => 1500,
            'category' => 'grade_10_to_11',
            'medium' => 'english',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Test Student',
            'full_name' => 'Test Student Full',
            'email' => 'student@tit.lk',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics']),
        ]);
    }

    public function test_admin_student_edit_shows_running_total_in_payment_history(): void
    {
        $ym = '2026-10';

        // Monthly payment 1500
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Top-up payment 1200
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1200,
            'kind' => 'topup',
            'status' => 'paid',
            'paid_at' => now(),
            'meta' => ['added_subjects' => ['Science']],
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.students.edit', $this->student->id));

        $response->assertStatus(200);
        $response->assertSee('Paid this month: Rs 2,700.00 (monthly Rs 1,500.00 + top-ups Rs 1,200.00)');
    }

    public function test_pending_payments_topup_row_shows_running_total(): void
    {
        $ym = '2026-10';

        // Monthly payment paid
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Pending top-up
        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1200,
            'status' => 'pending',
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
        ]);

        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1200,
            'kind' => 'topup',
            'status' => 'pending',
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Science'],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.students.pending-payments'));

        $response->assertStatus(200);
        $response->assertSee('Paid this month: Rs 1,500.00 (monthly Rs 1,500.00 + top-ups Rs 0.00)');
    }
}
