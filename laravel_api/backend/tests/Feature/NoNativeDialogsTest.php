<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class NoNativeDialogsTest extends TestCase
{
    /**
     * Scan all first-party Blade views and admin scripts for native dialogs.
     * Fails if any first-party use of alert(), confirm(), or prompt() is found.
     */
    public function test_no_first_party_native_dialogs_in_blade_or_admin_scripts(): void
    {
        $pattern = '/(?<![a-zA-Z0-9_$])(alert|confirm|prompt)\s*\(/i';

        $directoriesToScan = [
            resource_path('views'),
            public_path('admin-theme/js'),
        ];

        // Also scan root admin-theme/js if it exists
        $rootAdminThemeJs = realpath(base_path('../../admin-theme/js'));
        if ($rootAdminThemeJs && is_dir($rootAdminThemeJs)) {
            $directoriesToScan[] = $rootAdminThemeJs;
        }

        $violations = [];

        foreach ($directoriesToScan as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $filePath = $file->getPathname();
                $ext = pathinfo($filePath, PATHINFO_EXTENSION);

                // Only inspect .blade.php, .php, and .js files
                if (!in_array($ext, ['php', 'js'])) {
                    continue;
                }

                // Exclude vendor files and 3rd party plugins
                if (
                    str_contains($filePath, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) ||
                    str_contains($filePath, 'filament') ||
                    str_contains($filePath, 'plugins-init') ||
                    str_contains($filePath, 'sweetalert2') ||
                    str_contains($filePath, 'toastr')
                ) {
                    continue;
                }

                $lines = file($filePath, FILE_IGNORE_NEW_LINES);
                foreach ($lines as $lineNum => $line) {
                    $trimmed = trim($line);
                    // Skip pure comments
                    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
                        continue;
                    }

                    if (preg_match($pattern, $line, $matches)) {
                        // Allow first-party TitNotify custom dialog methods
                        if (str_contains($line, 'TitNotify.' . $matches[1])) {
                            continue;
                        }

                        $violations[] = sprintf(
                            '%s:%d - Found "%s" in: %s',
                            $filePath,
                            $lineNum + 1,
                            $matches[0],
                            $trimmed
                        );
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Found native dialog calls (alert/confirm/prompt) in first-party views or scripts:\n" . implode("\n", $violations)
        );
    }
}
