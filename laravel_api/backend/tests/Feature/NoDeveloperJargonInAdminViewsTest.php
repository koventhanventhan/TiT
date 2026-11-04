<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class NoDeveloperJargonInAdminViewsTest extends TestCase
{
    /**
     * Forbidden jargon patterns that should not appear in admin views
     * unless explicitly allow-listed with {{-- jargon-ok: reason --}}.
     */
    protected array $forbiddenPatterns = [
        'Implementation Note' => '/Implementation\s+Note/i',
        'artisan'             => '/\bartisan\b/i',
        'app:<command>'       => '/\bapp:[a-z-]+/i',
        'deactivated_at'      => '/\bdeactivated_at\b/',
        'payment_due'         => '/\bpayment_due\b/',
        'TODO/FIXME'          => '/\b(TODO|FIXME)\b/',
    ];

    public function test_no_developer_jargon_in_admin_views(): void
    {
        $adminViewsDir = resource_path('views/admin');
        $this->assertDirectoryExists($adminViewsDir);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($adminViewsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relativePath = str_replace($adminViewsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);

            foreach ($lines as $index => $line) {
                $lineNum = $index + 1;

                // Check each forbidden pattern
                foreach ($this->forbiddenPatterns as $name => $pattern) {
                    if (preg_match($pattern, $line)) {
                        // Check if the current line, previous line, or next line has an allow-list annotation
                        $prevLine = $index > 0 ? $lines[$index - 1] : '';
                        $nextLine = $index < count($lines) - 1 ? $lines[$index + 1] : '';

                        $isAllowed = str_contains($line, 'jargon-ok:')
                            || str_contains($prevLine, 'jargon-ok:')
                            || str_contains($nextLine, 'jargon-ok:');

                        if (!$isAllowed) {
                            $violations[] = "[{$name}] {$relativePath}:{$lineNum} -> " . trim($line);
                        }
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Developer jargon found in admin views without an allow-list comment:\n" . implode("\n", $violations)
        );
    }
}
