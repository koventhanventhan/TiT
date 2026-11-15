<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\ZoomAccount;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class IntegrationsEncryptZoomSecretsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'Encrypt Command Test Institute',
            'slug' => 'encrypt-command-test',
            'status' => 'active',
        ]);
    }

    /**
     * Test: a row with plaintext and a row already encrypted:
     * one run encrypts only the first, and a second run is idempotent.
     */
    public function test_encrypts_only_plaintext_rows_and_second_run_is_idempotent(): void
    {
        $plainSecret = 'plain_legacy_secret_12345';
        $encryptedSecret = Crypt::encryptString('already_encrypted_secret_67890');

        $plainId = DB::table('zoom_accounts')->insertGetId([
            'institute_id' => $this->institute->id,
            'email' => 'plain@zoom.com',
            'account_id' => 'ACC_PLAIN',
            'client_id' => 'CLIENT_PLAIN',
            'client_secret' => $plainSecret, // raw plaintext in DB
            'max_concurrent' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $alreadyEncryptedId = DB::table('zoom_accounts')->insertGetId([
            'institute_id' => $this->institute->id,
            'email' => 'encrypted@zoom.com',
            'account_id' => 'ACC_ENC',
            'client_id' => 'CLIENT_ENC',
            'client_secret' => $encryptedSecret, // already encrypted in DB
            'max_concurrent' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run 1: Dry run
        $this->artisan('integrations:encrypt-zoom-secrets', ['--dry-run' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('1 secret(s) to encrypt');

        // Plain row remains plain after dry run
        $this->assertSame($plainSecret, DB::table('zoom_accounts')->where('id', $plainId)->value('client_secret'));

        // Run 2: Real encryption run
        $this->artisan('integrations:encrypt-zoom-secrets')
            ->assertExitCode(0)
            ->expectsOutputToContain('Total Zoom accounts scanned')
            ->expectsOutputToContain('Already encrypted (safe)')
            ->expectsOutputToContain('1 secret(s) encrypted');

        // Row 1 is now encrypted
        $rawAfterRun = DB::table('zoom_accounts')->where('id', $plainId)->value('client_secret');
        $this->assertNotSame($plainSecret, $rawAfterRun);
        $this->assertSame($plainSecret, Crypt::decryptString($rawAfterRun));

        // Row 2 is unchanged
        $this->assertSame($encryptedSecret, DB::table('zoom_accounts')->where('id', $alreadyEncryptedId)->value('client_secret'));

        // Run 3: Idempotent run (second real run)
        $this->artisan('integrations:encrypt-zoom-secrets')
            ->assertExitCode(0)
            ->expectsOutputToContain('0 to encrypt');
    }

    /**
     * Test: a forced DB failure leaves every row unchanged (transaction rolled back)
     * and masks exception output without leaking ciphertext or secrets.
     */
    public function test_forced_db_failure_rolls_back_and_masks_secrets(): void
    {
        $plainSecret = 'sensitive_plain_secret_top_secret_xyz';

        $plainId = DB::table('zoom_accounts')->insertGetId([
            'institute_id' => $this->institute->id,
            'email' => 'fail_test@zoom.com',
            'account_id' => 'ACC_FAIL',
            'client_id' => 'CLIENT_FAIL',
            'client_secret' => $plainSecret,
            'max_concurrent' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Force DB failure on zoom_accounts update
        DB::listen(function ($query) use ($plainSecret) {
            if (str_contains($query->sql, 'update') && str_contains($query->sql, 'zoom_accounts')) {
                $fakeCipher = 'eyJpdiI6IlFVRVNUSU9OIiwidmFsdWUiOiJTVVBFUl9TRUNSRVRfQ0lQSEVSVEVYVCIsIm1hYyI6ImFiY2QifQ==';
                $pdoException = new PDOException(
                    "Data too long for column 'client_secret' at row 1",
                    22001
                );
                $pdoException->errorInfo = ['22001', 1406, "Data too long for column 'client_secret' at row 1"];

                throw new QueryException(
                    'mysql',
                    "update `zoom_accounts` set `client_secret` = '{$fakeCipher}' where `id` = 1",
                    [],
                    $pdoException
                );
            }
        });

        // Run command: must exit non-zero and print masked error
        $result = $this->artisan('integrations:encrypt-zoom-secrets');
        $result->assertExitCode(1);
        $result->expectsOutputToContain("Row ID {$plainId} failed: SQLSTATE[22001]: Data too long for column 'client_secret'");
        $result->doesntExpectOutputToContain('eyJpdiI');
        $result->doesntExpectOutputToContain($plainSecret);

        // Verify table is completely unchanged (rollback verified)
        $this->assertSame(
            $plainSecret,
            DB::table('zoom_accounts')->where('id', $plainId)->value('client_secret'),
            'Database failure must roll back and leave plain secret unchanged.'
        );
    }
}

