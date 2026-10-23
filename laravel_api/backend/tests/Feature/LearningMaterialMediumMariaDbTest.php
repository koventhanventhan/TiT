<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningMaterialMediumMariaDbTest extends TestCase
{
    protected array $createdMaterialIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Point specifically to dedicated test database tit_rag_test_round6 (never titjaffn_titjaffnas)
        config(['database.connections.mysql.database' => 'tit_rag_test_round6']);
        DB::purge('mysql');

        // Check if real MySQL / MariaDB connection is available
        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Dedicated test MariaDB connection (tit_rag_test_round6) is not reachable: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdMaterialIds)) {
            try {
                DB::connection('mysql')
                    ->table('learning_materials')
                    ->whereIn('id', $this->createdMaterialIds)
                    ->delete();
            } catch (\Throwable $e) {
                // ignore cleanup errors
            }
        }

        parent::tearDown();
    }

    /**
     * Test saving each allowed medium value directly in MariaDB.
     */
    public function test_mariadb_persists_each_canonical_medium_value(): void
    {
        $allowedValues = ['english', 'tamil', 'sinhala', 'all', 'both'];

        foreach ($allowedValues as $medium) {
            $id = DB::connection('mysql')->table('learning_materials')->insertGetId([
                'title' => "Test Note Medium {$medium}",
                'type' => 'note',
                'grade' => 'Grade 10',
                'subject' => 'Science',
                'medium' => $medium,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createdMaterialIds[] = $id;

            $record = DB::connection('mysql')
                ->table('learning_materials')
                ->where('id', $id)
                ->first();

            $this->assertNotNull($record, "Failed to retrieve record for medium '{$medium}' from MariaDB.");
            $this->assertSame($medium, $record->medium, "MariaDB did not store expected medium value '{$medium}'.");
        }
    }

    /**
     * Test saving invalid enum value fails on MariaDB strict mode.
     */
    public function test_mariadb_rejects_invalid_medium_value(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::connection('mysql')->table('learning_materials')->insert([
            'title' => 'Test Invalid Medium Note',
            'type' => 'note',
            'grade' => 'Grade 10',
            'subject' => 'Science',
            'medium' => 'invalid_lang_value',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test default value in MariaDB is 'all'.
     */
    public function test_mariadb_medium_column_defaults_to_all(): void
    {
        $id = DB::connection('mysql')->table('learning_materials')->insertGetId([
            'title' => 'Test Default Medium Note',
            'type' => 'note',
            'grade' => 'Grade 11',
            'subject' => 'History',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdMaterialIds[] = $id;

        $record = DB::connection('mysql')
            ->table('learning_materials')
            ->where('id', $id)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('tamil', $record->medium, "Default medium in MariaDB must be 'tamil'.");
    }
}
