<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\User;
use App\Models\ZoomAccount;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
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

    /**
     * PART D: Verify on MariaDB that creating and editing a Zoom account from the
     * Blade admin controller with a new secret works, and leaving the secret field empty keeps the old secret.
     */
    public function test_mariadb_admin_zoom_form_create_and_edit_flow(): void
    {
        $instituteId = $this->getOrCreateTestInstituteId();

        $adminUser = User::create([
            'name' => 'MariaDB Admin ' . uniqid(),
            'email' => 'mariadb_admin_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'institute_id' => $instituteId,
        ]);
        $this->cleanupUserId = $adminUser->id;

        // 1. Create a Zoom account from Admin form with a realistic secret
        $secret40 = 'admin_secret_' . bin2hex(random_bytes(10));
        $accountEmail = 'admin_zoom_' . uniqid() . '@example.com';

        $storeResponse = $this->actingAs($adminUser, 'web')->post(route('admin.zoom-accounts.store'), [
            'email' => $accountEmail,
            'account_id' => 'ACC_MARIADB_' . uniqid(),
            'client_id' => 'CLIENT_MARIADB_' . uniqid(),
            'client_secret' => $secret40,
            'max_concurrent' => 2,
            'is_active' => '1',
        ]);

        $storeResponse->assertRedirect(route('admin.zoom-accounts.index'));
        $storeResponse->assertSessionHas('success');

        $created = ZoomAccount::where('email', $accountEmail)->first();
        $this->assertNotNull($created);
        $this->cleanupAccountIds[] = $created->id;

        // Verify MariaDB stored it encrypted, and cast decrypts it properly
        $rawStoredSecret = DB::connection('mysql')->table('zoom_accounts')->where('id', $created->id)->value('client_secret');
        $this->assertNotSame($secret40, $rawStoredSecret);
        $this->assertSame($secret40, $created->client_secret);

        // 2. Edit the Zoom account with a NEW secret
        $newSecret = 'new_updated_secret_' . bin2hex(random_bytes(12));
        $updateResponse = $this->actingAs($adminUser, 'web')->put(route('admin.zoom-accounts.update', $created->id), [
            'email' => $accountEmail,
            'account_id' => $created->account_id,
            'client_id' => $created->client_id,
            'client_secret' => $newSecret,
            'max_concurrent' => 3,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.zoom-accounts.index'));
        $updateResponse->assertSessionHas('success');

        $refreshed = ZoomAccount::find($created->id);
        $this->assertSame($newSecret, $refreshed->client_secret);
        $this->assertSame(3, $refreshed->max_concurrent);

        // 3. Edit the Zoom account leaving client_secret field EMPTY (keeps old secret)
        $emptySecretUpdateResponse = $this->actingAs($adminUser, 'web')->put(route('admin.zoom-accounts.update', $created->id), [
            'email' => $accountEmail,
            'account_id' => $created->account_id,
            'client_id' => $created->client_id,
            'client_secret' => '',
            'max_concurrent' => 4,
            'is_active' => '1',
        ]);

        $emptySecretUpdateResponse->assertRedirect(route('admin.zoom-accounts.index'));
        $emptySecretUpdateResponse->assertSessionHas('success');

        $refreshedAfterEmpty = ZoomAccount::find($created->id);
        $this->assertSame($newSecret, $refreshedAfterEmpty->client_secret);
        $this->assertSame(4, $refreshedAfterEmpty->max_concurrent);
    }
}
