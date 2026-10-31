<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminContrastCrawlerTest extends TestCase
{
    /**
     * Test admin contrast crawler across all 4 presets and viewports.
     * Asserts WCAG AA >= 4.5:1 (>= 3:1 for large text) and zero horizontal overflow.
     */
    public function test_admin_contrast_and_overflow_crawler(): void
    {
        $rootDir = realpath(base_path('../../'));
        $crawlerScript = $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'admin_contrast_crawler.cjs';

        $this->assertFileExists($crawlerScript, 'admin_contrast_crawler.cjs must exist');

        // Check if local dev server is responding
        $appUrl = env('APP_URL', 'http://127.0.0.1:8000');
        $headers = @get_headers($appUrl);
        if (!$headers) {
            $this->markTestSkipped("Local web server not running at {$appUrl}. Skipping live Playwright crawl.");
        }

        $output = [];
        $exitCode = 0;
        exec("node " . escapeshellarg($crawlerScript) . " 2>&1", $output, $exitCode);

        $outputText = implode("\n", $output);
        $this->assertEquals(0, $exitCode, "Admin contrast crawler failed with exit code {$exitCode}:\n{$outputText}");
        $this->assertStringContainsString('0 contrast violations', $outputText);
        $this->assertStringContainsString('0 horizontal overflows', $outputText);
    }
}

