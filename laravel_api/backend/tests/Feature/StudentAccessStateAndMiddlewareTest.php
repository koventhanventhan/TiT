<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAccessStateAndMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'Access Test Institute',
            'slug' => 'access-test',
            'status' => 'active',
        ]);
    }

    public function test_student_access_state_returns_deactivated_when_deactivated_at_set(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => now(),
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        $this->assertEquals('deactivated', $student->studentAccessState());
    }

    public function test_student_access_state_returns_pending_registration_when_pending_payment(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => null,
            'registration_status' => 'pending_payment',
            'admin_confirmed_at' => now(),
        ]);

        $this->assertEquals('pending_registration', $student->studentAccessState());
    }

    public function test_student_access_state_returns_pending_approval_when_admin_not_confirmed(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => null,
            'registration_status' => 'approved',
            'admin_confirmed_at' => null,
        ]);

        $this->assertEquals('pending_approval', $student->studentAccessState());
    }

    public function test_student_access_state_returns_payment_due_when_unpaid_for_current_month(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => null,
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        // Unpaid
        $this->assertEquals('payment_due', $student->studentAccessState());

        // Paid for previous month, but current month is unpaid
        Payment::create([
            'user_id' => $student->id,
            'amount' => 1500,
            'status' => 'paid',
            'year_month' => now()->subMonth()->format('Y-m'),
            'payment_method' => 'online',
            'institute_id' => $this->institute->id,
        ]);

        $this->assertEquals('payment_due', $student->studentAccessState());
    }

    public function test_student_access_state_returns_ok_when_active_registered_approved_and_paid(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => null,
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        Payment::create([
            'user_id' => $student->id,
            'amount' => 1500,
            'status' => 'paid',
            'year_month' => now()->format('Y-m'),
            'payment_method' => 'online',
            'institute_id' => $this->institute->id,
        ]);

        $this->assertEquals('ok', $student->studentAccessState());
    }

    public function test_middleware_blocks_deactivated_student_with_expected_payload(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => now(),
            'admin_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($student)->getJson('/api/student/zoom-classes');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Your account has been deactivated. Please contact administration.',
            ]);
    }

    public function test_middleware_blocks_pending_registration_with_redirect_register(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => null,
            'registration_status' => 'pending_payment',
            'admin_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($student)->getJson('/api/student/zoom-classes');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Please complete your registration payment first.',
                'registration_status' => 'pending_payment',
                'redirect' => '/register',
            ]);
    }

    public function test_middleware_blocks_pending_approval_with_is_confirmed_false(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => null,
            'registration_status' => 'payment_completed',
            'admin_confirmed_at' => null,
        ]);

        $response = $this->actingAs($student)->getJson('/api/student/zoom-classes');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Your account is pending admin approval.',
                'registration_status' => 'payment_completed',
                'is_confirmed' => false,
            ]);
    }

    public function test_middleware_blocks_payment_due_with_needs_payment_true(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => null,
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($student)->getJson('/api/student/zoom-classes');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Please complete your monthly payment to access the dashboard.',
                'registration_status' => 'approved',
                'needs_payment' => true,
                'redirect' => '/student/payment',
            ]);
    }

    public function test_middleware_allows_fully_eligible_student(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'deactivated_at' => null,
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        Payment::create([
            'user_id' => $student->id,
            'amount' => 1500,
            'status' => 'paid',
            'year_month' => now()->format('Y-m'),
            'payment_method' => 'online',
            'institute_id' => $this->institute->id,
        ]);

        $response = $this->actingAs($student)->getJson('/api/student/messages');

        $response->assertStatus(200);
    }
}
