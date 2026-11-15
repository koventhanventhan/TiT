<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\User;
use App\Models\ZoomAccount;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ZoomAccountMariaDbTest extends TestCase
{
    private string $targetDatabase = 'tit_rag_test_round6';
    private array $cleanupAccountIds = [];
    private ?int $cleanupInstituteId = null;
    private ?int $cleanupUserId = null;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql.database' => $this->targetDatabase]);
        DB::purge('mysql');

        try {
            DB::connection('mysql')->getPdo();
            DB::setDefaultConnection('mysql');
        } catch (\Throwable $e) {
            $this->markTestSkipped("Dedicated test MariaDB ({$this->targetDatabase}) is not reachable: " . $e->getMessage());
        }

        // Run real migrations on MariaDB test database
        Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]);
    }

    protected function tearDown(): void
    {
        try {
            if (!empty($this->cleanupAccountIds)) {
                DB::connection('mysql')->table('zoom_accounts')->whereIn('id', $this->cleanupAccountIds)->delete();
            }
            if ($this->cleanupUserId) {
                DB::connection('mysql')->table('users')->where('id', $this->cleanupUserId)->delete();
            }
            if ($this->cleanupInstituteId) {
                DB::connection('mysql')->table('institutes')->where('id', $this->cleanupInstituteId)->delete();
            }
        } catch (\Throwable $e) {
            // ignore
        } finally {
            DB::setDefaultConnection('sqlite');
        }

        parent::tearDown();
    }

    private function getOrCreateTestInstituteId(): int
    {
        $inst = DB::connection('mysql')->table('institutes')->where('id', 1)->first();
        if ($inst) {
            return 1;
        }

        $id = DB::connection('mysql')->table('institutes')->insertGetId([
            'name' => 'MariaDB Test Institute ' . uniqid(),
            'slug' => 'mariadb-zoom-' . uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupInstituteId = $id;
        return $id;
    }

    /**
     * Test creating a ZoomAccount with a realistic 40-character secret through the model (cast on).
     */
    public function test_mariadb_creates_zoom_account_with_realistic_40_char_secret_and_reads_back(): void
    {
        $instituteId = $this->getOrCreateTestInstituteId();
        $secret40 = 'abc123def456ghi789jkl012mno345pqr678stu9'; // 40 chars

        $account = ZoomAccount::create([
            'institute_id' => $instituteId,
            'email' => 'zoom_40_' . uniqid() . '@example.com',
            'account_id' => 'zoom_acc_' . uniqid(),
            'client_id' => 'zoom_client_' . uniqid(),
            'client_secret' => $secret40,
            'max_concurrent' => 2,
            'is_active' => true,
        ]);

        $this->cleanupAccountIds[] = $account->id;

        $this->assertNotNull($account->id);

        $fresh = ZoomAccount::find($account->id);
        $this->assertNotNull($fresh);
        $this->assertSame($secret40, $fresh->client_secret);
    }

    /**
     * Test saving a ZoomAccount with a 200-character secret through the model.
     */
    public function test_mariadb_creates_zoom_account_with_200_char_secret(): void
    {
        $instituteId = $this->getOrCreateTestInstituteId();
        $secret200 = str_repeat('a1b2c3d4e5', 20); // 200 chars

        $account = ZoomAccount::create([
            'institute_id' => $instituteId,
            'email' => 'zoom_200_' . uniqid() . '@example.com',
            'account_id' => 'zoom_acc_' . uniqid(),
            'client_id' => 'zoom_client_' . uniqid(),
            'client_secret' => $secret200,
            'max_concurrent' => 1,
            'is_active' => true,
        ]);

        $this->cleanupAccountIds[] = $account->id;

        $this->assertNotNull($account->id);

        $fresh = ZoomAccount::find($account->id);
        $this->assertNotNull($fresh);
        $this->assertSame($secret200, $fresh->client_secret);
    }
}

