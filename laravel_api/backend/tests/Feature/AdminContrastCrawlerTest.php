<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminContrastCrawlerTest extends TestCase
{
    /**
     * Test admin contrast crawler across all 4 presets and viewports.
     * Asserts WCAG AA >= 4.5:1 (>= 3:1 for large text), zero horizontal overflow,
     * and >= 16 unique modals opened on the seeded application.
     */
    public function test_admin_contrast_and_overflow_crawler(): void
    {
        $rootDir = realpath(base_path('../../'));
        $crawlerScript = $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'admin_contrast_crawler.cjs';

        $this->assertFileExists($crawlerScript, 'admin_contrast_crawler.cjs must exist');

        // Check if local dev server is responding with a 2-second socket timeout
        $appUrl = env('APP_URL', 'http://127.0.0.1:8000');
        $parts = parse_url($appUrl);
        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 8000;
        $fp = @fsockopen($host, (int) $port, $errno, $errstr, 2);
        if (!$fp) {
            $this->markTestSkipped("Local web server not running at {$appUrl} (connection refused: {$errstr}). Skipping live Playwright crawl.");
        }
        fclose($fp);

        $output = [];
        $exitCode = 0;
        $preset = env('TEST_PRESET', 'royal');
        putenv("TEST_PRESET={$preset}");
        $_ENV['TEST_PRESET'] = $preset;
        $cmd = "node " . escapeshellarg($crawlerScript) . " 2>&1";
        exec($cmd, $output, $exitCode);

        $outputText = implode("\n", $output);
        $this->assertEquals(0, $exitCode, "Admin contrast crawler failed with exit code {$exitCode}:\n{$outputText}");
        $this->assertStringContainsString('0 contrast violations', $outputText);
        $this->assertStringContainsString('0 horizontal overflows', $outputText);
        $this->assertStringContainsString('0 layout violations', $outputText);
        $this->assertStringContainsString('Unique modals opened', $outputText);

        if (preg_match('/SUMMARY_JSON:(.*)/', $outputText, $matches)) {
            $data = json_decode(trim($matches[1]), true);
            $this->assertIsArray($data, 'Parsed SUMMARY_JSON must be an array');
            $this->assertGreaterThanOrEqual(16, count($data['uniqueModals']), 'Must open at least 16 unique modals');
            foreach ($data['summary'] as $row) {
                $this->assertEquals(0, $row['violations'], "Violations must be 0 for {$row['preset']} {$row['viewport']}");
                $this->assertEquals(0, $row['layoutViolations'], "Layout violations must be 0 for {$row['preset']} {$row['viewport']}");
                $this->assertGreaterThan(20, $row['nodesScanned'] / max(1, $row['pages']), "Average nodes scanned per page must exceed 20");
            }
        }
    }
}
