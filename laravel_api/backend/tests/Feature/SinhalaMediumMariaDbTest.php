<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SinhalaMediumMariaDbTest extends TestCase
{
    protected ?int $cleanupInstituteId = null;
    protected array $createdUserIds = [];
    protected array $createdScheduleIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Connect to dedicated test database tit_rag_test_round6 (never dev DB)
        config(['database.connections.mysql.database' => 'tit_rag_test_round6']);
        DB::purge('mysql');

        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Dedicated test MariaDB connection (tit_rag_test_round6) is not reachable: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdScheduleIds)) {
            try {
                DB::connection('mysql')->table('zoom_schedules')->whereIn('id', $this->createdScheduleIds)->delete();
            } catch (\Throwable $e) {}
        }

        if (!empty($this->createdUserIds)) {
            try {
                DB::connection('mysql')->table('users')->whereIn('id', $this->createdUserIds)->delete();
            } catch (\Throwable $e) {}
        }

        if ($this->cleanupInstituteId) {
            try {
                DB::connection('mysql')->table('institutes')->where('id', $this->cleanupInstituteId)->delete();
            } catch (\Throwable $e) {}
        }

        parent::tearDown();
    }

    /**
     * Test saving a Sinhala-medium student and a Sinhala class in dedicated test MariaDB.
     */
    public function test_mariadb_persists_sinhala_medium_student_and_class(): void
    {
        // 1. Ensure the migration has altered the columns in test MariaDB
        DB::connection('mysql')->statement("ALTER TABLE `users` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'sinhala') NULL DEFAULT NULL");
        DB::connection('mysql')->statement("ALTER TABLE `zoom_schedules` MODIFY COLUMN `medium` ENUM('english', 'tamil', 'sinhala', 'both', 'all') NULL DEFAULT 'tamil'");

        // 2. Ensure an Institute exists for FK constraint
        $instituteId = DB::connection('mysql')->table('institutes')->insertGetId([
            'name' => 'Sinhala Test Institute ' . uniqid(),
            'slug' => 'sinhala-test-' . uniqid(),
            'status' => 'active',
            'rag_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupInstituteId = $instituteId;

        // 3. Insert Sinhala-medium student
        $userId = DB::connection('mysql')->table('users')->insertGetId([
            'name' => 'Sinhala Test Student',
            'email' => 'sinhala_test_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'user',
            'medium' => 'sinhala',
            'current_grade' => 'Grade 10',
            'institute_id' => $instituteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdUserIds[] = $userId;

        $user = DB::connection('mysql')->table('users')->where('id', $userId)->first();
        $this->assertNotNull($user);
        $this->assertEquals('sinhala', $user->medium);

        // 4. Insert Sinhala class (zoom_schedule)
        $scheduleId = DB::connection('mysql')->table('zoom_schedules')->insertGetId([
            'title' => 'Grade 10 Science Sinhala Medium Live Class',
            'zoom_link' => 'https://zoom.us/j/1234567890',
            'scheduled_at' => now()->addDays(2),
            'duration' => 60,
            'grade' => 'Grade 10',
            'subject' => 'Science',
            'medium' => 'sinhala',
            'created_by' => $userId,
            'institute_id' => $instituteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdScheduleIds[] = $scheduleId;

        $schedule = DB::connection('mysql')->table('zoom_schedules')->where('id', $scheduleId)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('sinhala', $schedule->medium);
    }
}
