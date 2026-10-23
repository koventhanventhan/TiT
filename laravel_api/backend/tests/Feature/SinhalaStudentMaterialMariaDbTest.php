<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Services\Rag\RetrievalService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SinhalaStudentMaterialMariaDbTest extends TestCase
{
    private string $targetDatabase = 'tit_rag_test_round6';
    private array $cleanupMaterialIds = [];
    private array $cleanupUserIds = [];
    private ?int $cleanupInstituteId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Direct connection specifically to dedicated test database tit_rag_test_round6 (never titjaffn_titjaffnas)
        config(['database.connections.mysql.database' => $this->targetDatabase]);
        DB::purge('mysql');

        // Check if real MySQL / MariaDB connection is available
        try {
            DB::connection('mysql')->getPdo();
            DB::setDefaultConnection('mysql');
        } catch (\Throwable $e) {
            $this->markTestSkipped("Dedicated test MariaDB ({$this->targetDatabase}) is not reachable: " . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        try {
            if (!empty($this->cleanupMaterialIds)) {
                DB::connection('mysql')->table('learning_materials')->whereIn('id', $this->cleanupMaterialIds)->delete();
            }
            if (!empty($this->cleanupUserIds)) {
                DB::connection('mysql')->table('users')->whereIn('id', $this->cleanupUserIds)->delete();
            }
            if ($this->cleanupInstituteId) {
                DB::connection('mysql')->table('institutes')->where('id', $this->cleanupInstituteId)->delete();
            }
        } catch (\Throwable $e) {
            // Ignore cleanup errors
        } finally {
            DB::setDefaultConnection('sqlite');
        }

        parent::tearDown();
    }

    /**
     * Test on MariaDB (tit_rag_test_round6) that a Sinhala student can see Sinhala & All materials,
     * but strictly cannot see English or Tamil materials.
     */
    public function test_mariadb_sinhala_student_sees_sinhala_and_not_english_material(): void
    {
        // 1. Create Institute
        $instituteId = DB::connection('mysql')->table('institutes')->insertGetId([
            'name' => 'MariaDB Test Institute ' . uniqid(),
            'slug' => 'mariadb-test-' . uniqid(),
            'status' => 'active',
            'rag_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupInstituteId = $instituteId;

        // 2. Insert Materials directly into MariaDB
        $sinhalaMatId = DB::connection('mysql')->table('learning_materials')->insertGetId([
            'institute_id' => $instituteId,
            'title' => 'Grade 10 Sinhala Science Notes',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'sinhala',
            'subject' => 'Science',
            'rag_status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupMaterialIds[] = $sinhalaMatId;

        $englishMatId = DB::connection('mysql')->table('learning_materials')->insertGetId([
            'institute_id' => $instituteId,
            'title' => 'Grade 10 English Science Notes',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'english',
            'subject' => 'Science',
            'rag_status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupMaterialIds[] = $englishMatId;

        $tamilMatId = DB::connection('mysql')->table('learning_materials')->insertGetId([
            'institute_id' => $instituteId,
            'title' => 'Grade 10 Tamil Science Notes',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'rag_status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupMaterialIds[] = $tamilMatId;

        $allMatId = DB::connection('mysql')->table('learning_materials')->insertGetId([
            'institute_id' => $instituteId,
            'title' => 'Grade 10 Universal Formula Sheet',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'all',
            'subject' => 'Science',
            'rag_status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupMaterialIds[] = $allMatId;

        // 3. Create Sinhala and English Student models
        $sinhalaStudent = new User();
        $sinhalaStudent->id = 999901;
        $sinhalaStudent->institute_id = $instituteId;
        $sinhalaStudent->current_grade = 'grade-10';
        $sinhalaStudent->medium = 'sinhala';
        $sinhalaStudent->role = 'user';

        $englishStudent = new User();
        $englishStudent->id = 999902;
        $englishStudent->institute_id = $instituteId;
        $englishStudent->current_grade = 'grade-10';
        $englishStudent->medium = 'english';
        $englishStudent->role = 'user';

        // 4. Test RetrievalService filtering
        $retrievalService = app(RetrievalService::class);

        $sinhalaAllowed = $retrievalService->getAllowedMaterialIds($sinhalaStudent);
        $englishAllowed = $retrievalService->getAllowedMaterialIds($englishStudent);

        // Sinhala student checks:
        $this->assertContains($sinhalaMatId, $sinhalaAllowed, "Sinhala student must see Sinhala material in MariaDB.");
        $this->assertContains($allMatId, $sinhalaAllowed, "Sinhala student must see 'all' medium material in MariaDB.");
        $this->assertNotContains($englishMatId, $sinhalaAllowed, "Sinhala student must NOT see English material in MariaDB.");
        $this->assertNotContains($tamilMatId, $sinhalaAllowed, "Sinhala student must NOT see Tamil material in MariaDB.");

        // English student checks:
        $this->assertContains($englishMatId, $englishAllowed, "English student must see English material in MariaDB.");
        $this->assertContains($allMatId, $englishAllowed, "English student must see 'all' medium material in MariaDB.");
        $this->assertNotContains($sinhalaMatId, $englishAllowed, "English student must NOT see Sinhala material in MariaDB.");
        $this->assertNotContains($tamilMatId, $englishAllowed, "English student must NOT see Tamil material in MariaDB.");
    }
}
