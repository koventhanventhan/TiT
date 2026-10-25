<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class AdminBladeUrlLintTest extends TestCase
{
    /**
     * Ensure no admin Blade view contains hard-coded string literals starting with '/admin/'
     * outside route( or asset( helpers.
     */
    public function test_no_hardcoded_admin_urls_in_admin_blade_views(): void
    {
        $adminViewsPath = resource_path('views/admin');
        $this->assertDirectoryExists($adminViewsPath);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($adminViewsPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $lines = file($file->getPathname());
            foreach ($lines as $lineNum => $line) {
                // Match string literals like '/admin/...', "/admin/...", `/admin/...`
                if (preg_match_all('/([\'"`])\/admin\/[^\'"`]*\1/', $line, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $matchedString = $match[0];
                        $offset = $match[1];
                        
                        // Check if this match is inside route(...) or asset(...)
                        $before = substr($line, 0, $offset);
                        // If preceding code contains route( or asset( with no closing paren before the match
                        $insideHelper = (bool) preg_match('/(?:route|asset)\s*\([^)]*$/', $before);

                        if (!$insideHelper) {
                            $violations[] = sprintf(
                                '%s:%d - Found "%s"',
                                str_replace(resource_path('views/admin') . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                                $lineNum + 1,
                                $matchedString
                            );
                        }
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Admin views must not contain hard-coded '/admin/...' URLs outside route() or asset(). Violations found:\n" . implode("\n", $violations)
        );
    }
}
