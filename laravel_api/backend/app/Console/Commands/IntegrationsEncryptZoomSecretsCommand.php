<?php

namespace App\Console\Commands;

use App\Models\ZoomAccount;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

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
            } catch (\Throwable) {
                $isEncrypted = false;
            }

            if ($isEncrypted) {
                $alreadyEncrypted++;
            } else {
                $plainFound++;
                if (!$dryRun) {
                    $encryptedValue = Crypt::encryptString((string) $rawSecret);
                    DB::table('zoom_accounts')
                        ->where('id', $account->id)
                        ->update(['client_secret' => $encryptedValue]);
                    $encryptedCount++;
                }
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Zoom accounts scanned', (string) $total],
                ['Already encrypted (safe)', (string) $alreadyEncrypted],
                ['Empty secrets', (string) $emptySecrets],
                ['Plaintext secrets found', (string) $plainFound],
                [$dryRun ? 'Secrets to encrypt (dry-run)' : 'Secrets encrypted successfully', (string) ($dryRun ? $plainFound : $encryptedCount)],
            ]
        );

        if ($dryRun) {
            $this->comment('Dry-run complete. No database changes were made.');
        } else {
            $this->info("Completed. {$encryptedCount} secret(s) encrypted.");
        }

        return Command::SUCCESS;
    }
}
