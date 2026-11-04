<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class NoNestedContentBodyTest extends TestCase
{
    /**
     * Test that no view under resources/views/admin/** that extends layouts.admin
     * contains class="content-body", preventing double margin-left and shifted layouts.
     */
    public function test_no_nested_content_body_in_admin_views(): void
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

            $content = file_get_contents($file->getPathname());

            // Check if this view extends layouts.admin
            $extendsAdminLayout = preg_match('/@extends\s*\(\s*[\'"]layouts\.admin[\'"]\s*\)/', $content);
            if (!$extendsAdminLayout) {
                continue;
            }

            // Check if it contains class="content-body" or class='content-body'
            if (preg_match('/class=[\'"][^\'"]*\bcontent-body\b[^\'"]*[\'"]/', $content, $match, PREG_OFFSET_CAPTURE)) {
                $relativePath = str_replace($adminViewsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $offset = $match[0][1];
                $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
                $violations[] = "{$relativePath}:{$lineNum} -> {$match[0][0]}";
            }
        }

        $this->assertEmpty(
            $violations,
            "Found views extending layouts.admin with nested .content-body wrappers:\n" . implode("\n", $violations)
        );
    }
}

