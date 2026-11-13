<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class NoDirectMailOrGlobalIntegrationConfigTest extends TestCase
{
    public function test_no_direct_mail_to_or_raw_outside_institute_mail_wrapper(): void
    {
        $allowedFiles = [
            realpath(app_path('Support/InstituteMail.php')),
            realpath(app_path('Support/InstitutePendingMail.php')),
        ];

        $violations = [];

        foreach ($this->phpFilesIn([app_path(), base_path('routes')]) as $path) {
            $real = realpath($path);
            if (in_array($real, $allowedFiles, true)) {
                continue;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lines as $idx => $line) {
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                if (str_contains($line, '// integration-ok:')) {
                    continue;
                }

                // Match Facade Mail::to( or Mail::raw( (including \Illuminate\Support\Facades\Mail::to(), excluding InstituteMail::to/raw)
                if (preg_match('/(?<![A-Za-z0-9_])Mail::(to|raw)\s*\(/', $line)) {
                    $rel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
                    $violations[] = "{$rel}:" . ($idx + 1) . ": " . trim($line);
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Direct Mail::to() / Mail::raw() calls are forbidden outside App\\Support\\InstituteMail:\n" . implode("\n", $violations)
        );
    }

    public function test_no_global_payhere_or_meta_whatsapp_config_reads_outside_integrations_resolver(): void
    {
        $allowedFiles = [
            realpath(app_path('Support/Integrations.php')),
        ];

        $violations = [];

        foreach ($this->phpFilesIn([app_path(), base_path('routes')]) as $path) {
            $real = realpath($path);
            if (in_array($real, $allowedFiles, true)) {
                continue;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lines as $idx => $line) {
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                if (str_contains($line, '// integration-ok:')) {
                    continue;
                }

                if (preg_match('/config\s*\(\s*[\'"](?:payhere|services\.meta_whatsapp)\b/', $line)) {
                    $rel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
                    $violations[] = "{$rel}:" . ($idx + 1) . ": " . trim($line);
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Global config('payhere...') / config('services.meta_whatsapp...') reads are forbidden outside App\\Support\\Integrations:\n" . implode("\n", $violations)
        );
    }

    public function test_no_direct_gemini_zoom_recaptcha_google_config_reads_outside_integrations_resolver(): void
    {
        $allowedFiles = [
            realpath(app_path('Support/Integrations.php')),
        ];

        $forbiddenPatterns = [
            'services.gemini.api_key',
            'services.zoom.client_secret',
            'services.recaptcha.secret_key',
            'services.google.client_secret',
        ];

        $regex = '/config\s*\(\s*[\'"](?:' . implode('|', array_map('preg_quote', $forbiddenPatterns)) . ')[\'"]\s*[,)]/';

        $violations = [];

        foreach ($this->phpFilesIn([app_path(), base_path('routes')]) as $path) {
            $real = realpath($path);
            if (in_array($real, $allowedFiles, true)) {
                continue;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lines as $idx => $line) {
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                if (str_contains($line, '// integration-ok:')) {
                    continue;
                }

                if (preg_match($regex, $line)) {
                    $rel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
                    $violations[] = "{$rel}:" . ($idx + 1) . ": " . trim($line);
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Direct config('services.gemini.api_key'|'services.zoom.client_secret'|'services.recaptcha.secret_key'|'services.google.client_secret') reads are forbidden outside App\\Support\\Integrations:\n" . implode("\n", $violations)
        );
    }

    /**
     * @param array<int, string> $dirs
     * @return array<int, string>
     */
    private function phpFilesIn(array $dirs): array
    {
        $files = [];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($it as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }
        sort($files);
        return $files;
    }
}
