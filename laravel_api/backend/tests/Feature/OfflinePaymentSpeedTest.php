<?php

namespace Tests\Feature;

use App\Models\AdvancePaymentRequest;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfflinePaymentSpeedTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
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

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Koventhan',
            'full_name' => 'Koventhan Student',
            'email' => 'koventhan@student.com',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics', 'Science']),
        ]);
    }

    public function test_offline_monthly_payment_returns_200_and_creates_payment_record()
    {
        Sanctum::actingAs($this->student);

        // Mock NotificationService to simulate slow/failing notification
        $mock = $this->createMock(NotificationService::class);
        $mock->method('notifyUser')->willThrowException(new \RuntimeException('Notification service timeout'));
        $this->app->instance(NotificationService::class, $mock);

        $response = $this->postJson('/api/student/pay-monthly-offline');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Offline payment initiated. Please pay offline. Admin will confirm and you will receive a WhatsApp message.',
            ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->student->id,
            'payment_method' => 'offline',
            'status' => 'pending',
            'year_month' => now()->format('Y-m'),
        ]);
    }

    public function test_offline_advance_payment_returns_200_and_creates_advance_request()
    {
        Sanctum::actingAs($this->student);

        // Mock NotificationService to simulate failing notification
        $mock = $this->createMock(NotificationService::class);
        $mock->method('notifyAdmin')->willThrowException(new \RuntimeException('Admin notification timeout'));
        $this->app->instance(NotificationService::class, $mock);

        $response = $this->postJson('/api/student/advance-payment-offline', [
            'months' => 4,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'months' => 4,
                'message' => 'Advance payment requested successfully. Admin will confirm shortly.',
            ]);

        $this->assertDatabaseHas('advance_payment_requests', [
            'user_id' => $this->student->id,
            'months_count' => 4,
            'status' => 'pending',
        ]);
    }

    public function test_step2_offline_registration_payment_returns_200_and_updates_status()
    {
        $pendingStudent = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Newbie',
            'full_name' => 'Newbie Student',
            'email' => 'newbie@student.com',
            'registration_status' => 'pending_payment',
            'current_grade' => 'Grade 8',
            'medium' => 'tamil',
            'selected_subjects' => json_encode(['Tamil']),
        ]);

        Sanctum::actingAs($pendingStudent);

        // Mock NotificationService to simulate failing notification
        $mock = $this->createMock(NotificationService::class);
        $mock->method('notifyUser')->willThrowException(new \RuntimeException('SMS gateway error'));
        $this->app->instance(NotificationService::class, $mock);

        $response = $this->postJson('/api/register/step2', [
            'payment_method' => 'offline',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'registration_status' => 'payment_completed',
            ]);

        $this->assertEquals('payment_completed', $pendingStudent->fresh()->registration_status);
    }
}
