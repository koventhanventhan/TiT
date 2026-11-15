<?php

namespace App\Console\Commands;

use App\Models\ZoomAccount;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PDOException;
use Throwable;

class IntegrationsEncryptZoomSecretsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrations:encrypt-zoom-secrets {--dry-run : Only inspect without writing changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt plaintext Zoom client secrets in zoom_accounts table (idempotent, never prints secrets)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Running dry-run: scanning Zoom accounts for plaintext secrets...'
            : 'Scanning and encrypting plaintext Zoom client secrets...');

        $accounts = ZoomAccount::withoutGlobalScopes()->get();
        $total = $accounts->count();
        $alreadyEncrypted = 0;
        $emptySecrets = 0;
        $plainFound = 0;
        $encryptedCount = 0;
        $failedCount = 0;

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($accounts as $account) {
                $rawSecret = DB::table('zoom_accounts')
                    ->where('id', $account->id)
                    ->value('client_secret');

                if ($rawSecret === null || $rawSecret === '') {
                    $emptySecrets++;
                    continue;
                }

                $isEncrypted = false;
                try {
                    Crypt::decryptString((string) $rawSecret);
                    $isEncrypted = true;
                } catch (DecryptException) {
                    $isEncrypted = false;
                } catch (Throwable) {
                    $isEncrypted = false;
                }

                if ($isEncrypted) {
                    $alreadyEncrypted++;
                } else {
                    $plainFound++;
                    if (!$dryRun) {
                        try {
                            $encryptedValue = Crypt::encryptString((string) $rawSecret);
                            DB::table('zoom_accounts')
                                ->where('id', $account->id)
                                ->update(['client_secret' => $encryptedValue]);
                            $encryptedCount++;
                        } catch (Throwable $e) {
                            $failedCount++;
                            $shortReason = $this->formatShortReason($e);
                            $this->error("Row ID {$account->id} failed: {$shortReason}");
                            throw $e;
                        }
                    }
                }
            }

            if (!$dryRun) {
                DB::commit();
            }
        } catch (Throwable) {
            if (!$dryRun) {
                DB::rollBack();
            }

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Zoom accounts scanned', (string) $total],
                    ['Already encrypted (safe)', (string) $alreadyEncrypted],
                    ['Empty secrets', (string) $emptySecrets],
                    ['Plaintext secrets found', (string) $plainFound],
                    ['Secrets encrypted successfully', '0 (rolled back)'],
                    ['Failed', (string) ($failedCount > 0 ? $failedCount : 1)],
                ]
            );

            $this->error('Encryption aborted due to database failure. All changes rolled back; table left unchanged.');
            return Command::FAILURE;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Zoom accounts scanned', (string) $total],
                ['Already encrypted (safe)', (string) $alreadyEncrypted],
                ['Empty secrets', (string) $emptySecrets],
                ['Plaintext secrets found', (string) $plainFound],
                [$dryRun ? 'Secrets to encrypt (dry-run)' : 'Secrets encrypted successfully', (string) ($dryRun ? $plainFound : $encryptedCount)],
                ['Failed', '0'],
            ]
        );

        if ($dryRun) {
            $this->comment($plainFound === 0
                ? 'Dry-run complete. 0 to encrypt. No database changes were made.'
                : "Dry-run complete. {$plainFound} secret(s) to encrypt. No database changes were made.");
        } else {
            $this->info($encryptedCount === 0
                ? 'Completed. 0 to encrypt (all secrets already encrypted).'
                : "Completed. {$encryptedCount} secret(s) encrypted.");
        }

        return Command::SUCCESS;
    }

    /**
     * Format a safe short reason for DB errors that masks secrets and ciphertext.
     */
    private function formatShortReason(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            $sqlState = $e->getCode() ?: '22001';
            $previous = $e->getPrevious();
            $pdoMsg = ($previous instanceof PDOException && isset($previous->errorInfo[2]))
                ? $previous->errorInfo[2]
                : $e->getMessage();

            if (preg_match('/Data too long for column \'([^\']+)\'/i', $pdoMsg, $matches)) {
                return "SQLSTATE[{$sqlState}]: Data too long for column '{$matches[1]}'";
            }

            return "SQLSTATE[{$sqlState}] on column client_secret";
        }

        return 'Failed encrypting client_secret: ' . class_basename($e);
    }
}
