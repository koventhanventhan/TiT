<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Institute;
use App\Models\SiteSetting;

class AutoPromoteGradesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $institute = new Institute([
            'id' => 1,
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active'
        ]);
        $institute->save();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin_auto@test.com',
            'institute_id' => $institute->id
        ]);
    }

    public function test_auto_promote_fires_for_today_and_resets_toggle()
    {
        $today = now()->toDateString();

        // Create students in Grade 7
        $student1 = User::factory()->create([
            'role' => 'user',
            'current_grade' => '7',
            'institute_id' => 1,
            'last_promoted_at' => now()->subMonths(7),
        ]);
        $student2 = User::factory()->create([
            'role' => 'user',
            'current_grade' => '7',
            'institute_id' => 1,
            'last_promoted_at' => now()->subMonths(8),
        ]);

        // Create a Grade 8 student (should NOT be touched)
        $student3 = User::factory()->create([
            'role' => 'user',
            'current_grade' => '8',
            'institute_id' => 1,
            'last_promoted_at' => now()->subMonths(7),
        ]);

        // Set the schedule: Grade 7 enabled for today, Grade 8 disabled
        $schedule = [];
        foreach (range(1, 13) as $g) {
            $schedule[(string)$g] = ['enabled' => false, 'date' => null];
        }
        $schedule['7'] = ['enabled' => true, 'date' => $today];

        SiteSetting::set('grade_promotion_schedule', json_encode($schedule), 'general');

        // Run the command
        $this->artisan('promotions:auto-run')
             ->assertExitCode(0);

        // Assert Grade 7 students were promoted
        $student1->refresh();
        $student2->refresh();
        $this->assertEquals('8', $student1->current_grade);
        $this->assertEquals('8', $student2->current_grade);

        // Assert Grade 8 student was NOT touched
        $student3->refresh();
        $this->assertEquals('8', $student3->current_grade);

        // Assert the toggle was reset to false
        $updatedConfig = json_decode(SiteSetting::get('grade_promotion_schedule'), true);
        $this->assertFalse($updatedConfig['7']['enabled']);

        // Assert activity log was created
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'auto_grade_promotion',
        ]);
    }

    public function test_future_date_does_nothing()
    {
        $futureDate = now()->addDays(5)->toDateString();

        $student = User::factory()->create([
            'role' => 'user',
            'current_grade' => '9',
            'institute_id' => 1,
            'last_promoted_at' => now()->subMonths(7),
        ]);

        $schedule = [];
        foreach (range(1, 13) as $g) {
            $schedule[(string)$g] = ['enabled' => false, 'date' => null];
        }
        $schedule['9'] = ['enabled' => true, 'date' => $futureDate];

        SiteSetting::set('grade_promotion_schedule', json_encode($schedule), 'general');

        $this->artisan('promotions:auto-run')
             ->assertExitCode(0);

        // Student should NOT have been promoted
        $student->refresh();
        $this->assertEquals('9', $student->current_grade);

        // Toggle should still be enabled (not reset) since the date hasn't come
        $updatedConfig = json_decode(SiteSetting::get('grade_promotion_schedule'), true);
        $this->assertTrue($updatedConfig['9']['enabled']);
    }

    public function test_empty_grade_still_resets_toggle()
    {
        $today = now()->toDateString();

        // No students in Grade 10
        $schedule = [];
        foreach (range(1, 13) as $g) {
            $schedule[(string)$g] = ['enabled' => false, 'date' => null];
        }
        $schedule['10'] = ['enabled' => true, 'date' => $today];

        SiteSetting::set('grade_promotion_schedule', json_encode($schedule), 'general');

        $this->artisan('promotions:auto-run')
             ->assertExitCode(0);

        // Toggle should be reset to false even with no students
        $updatedConfig = json_decode(SiteSetting::get('grade_promotion_schedule'), true);
        $this->assertFalse($updatedConfig['10']['enabled']);
    }
}
