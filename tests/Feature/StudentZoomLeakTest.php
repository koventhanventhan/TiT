<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use App\Models\ZoomAccount;
use App\Models\ZoomSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentZoomLeakTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $teacher;
    private User $student;
    private User $admin;
    private ZoomAccount $zoomAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'Apex Institute',
            'slug' => 'apex-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $this->zoomAccount = ZoomAccount::create([
            'email' => 'zoom-admin@tit.edu',
            'account_id' => 'zoom_acc_root_123',
            'client_id' => 'client_id_xyz',
            'client_secret' => 'client_secret_secret',
            'institute_id' => $this->institute->id,
            'is_active' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute->id,
            'name' => 'Prof. Xavier',
            'full_name' => 'Charles Xavier',
            'email' => 'xavier.private@tit.edu',
            'phone_number' => '+94771234567',
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

    public function test_upcoming_schedules_does_not_leak_start_url_or_private_fields(): void
    {
        $schedule = ZoomSchedule::create([
            'title' => 'Maths Live Class',
            'zoom_link' => 'https://zoom.us/j/987654321',
            'join_url' => 'https://zoom.us/j/987654321?pwd=abc',
            'start_url' => 'https://zoom.us/s/987654321?zak=SECRET_HOST_ZAK_TOKEN',
            'meeting_id' => '987654321',
            'zoom_account_id' => $this->zoomAccount->id,
            'created_by' => $this->admin->id,
            'password' => 'secret123',
            'scheduled_at' => Carbon::now()->addHour(),
            'duration' => 60,
            'grade' => 'Grade 10',
            'medium' => 'tamil',
            'institute_id' => $this->institute->id,
        ]);
        $schedule->teachers()->attach($this->teacher->id);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/student/upcoming-schedules');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data, 'Response should contain upcoming schedules');

        foreach ($data as $item) {
            // Must NOT contain start_url (host ZAK key leak)
            $this->assertArrayNotHasKey('start_url', $item, 'start_url (host ZAK link) leaked to student in upcoming-schedules');
            // Must NOT contain zoom_account_id
            $this->assertArrayNotHasKey('zoom_account_id', $item, 'zoom_account_id leaked to student in upcoming-schedules');
            // Must NOT contain internal administrative fields
            $this->assertArrayNotHasKey('created_by', $item, 'created_by leaked to student in upcoming-schedules');
            $this->assertArrayNotHasKey('meeting_id', $item, 'meeting_id leaked to student in upcoming-schedules');
            $this->assertArrayNotHasKey('reminded_at', $item, 'reminded_at leaked to student in upcoming-schedules');
            // Must NOT contain raw teacher Eloquent model/relation with private contacts
            $this->assertArrayNotHasKey('teachers', $item, 'raw teachers relation leaked in upcoming-schedules');

            // Must contain whitelisted student fields
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('zoom_link', $item);
            $this->assertArrayHasKey('join_url', $item);
            $this->assertArrayHasKey('password', $item);
            $this->assertArrayHasKey('scheduled_at', $item);
            $this->assertArrayHasKey('duration', $item);
            $this->assertArrayHasKey('subject', $item);
            $this->assertArrayHasKey('grade', $item);
            $this->assertArrayHasKey('medium', $item);
            $this->assertArrayHasKey('teacher', $item);
            $this->assertEquals('Prof. Xavier', $item['teacher']);
        }

        // Ensure raw response does not contain secret host token or private teacher details
        $this->assertStringNotContainsString('SECRET_HOST_ZAK_TOKEN', $response->getContent());
        $this->assertStringNotContainsString('xavier.private@tit.edu', $response->getContent());
    }

    public function test_zoom_classes_endpoint_does_not_leak_start_url_or_private_fields(): void
    {
        $schedule = ZoomSchedule::create([
            'title' => 'Physics Live Class',
            'zoom_link' => 'https://zoom.us/j/123456789',
            'join_url' => 'https://zoom.us/j/123456789?pwd=xyz',
            'start_url' => 'https://zoom.us/s/123456789?zak=ANOTHER_SECRET_ZAK',
            'meeting_id' => '123456789',
            'zoom_account_id' => $this->zoomAccount->id,
            'created_by' => $this->admin->id,
            'password' => 'pass123',
            'scheduled_at' => Carbon::now()->addHour(),
            'duration' => 60,
            'grade' => 'Grade 10',
            'medium' => 'tamil',
            'institute_id' => $this->institute->id,
        ]);
        $schedule->teachers()->attach($this->teacher->id);

        Sanctum::actingAs($this->student);

        $response = $this->getJson('/api/student/zoom-classes');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $item) {
            $this->assertArrayNotHasKey('start_url', $item, 'start_url leaked to student in zoom-classes');
            $this->assertArrayNotHasKey('zoom_account_id', $item, 'zoom_account_id leaked to student in zoom-classes');
            $this->assertArrayNotHasKey('created_by', $item, 'created_by leaked to student in zoom-classes');
            $this->assertArrayNotHasKey('meeting_id', $item, 'meeting_id leaked to student in zoom-classes');
        }

        $this->assertStringNotContainsString('ANOTHER_SECRET_ZAK', $response->getContent());
    }
}