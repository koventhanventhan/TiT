<?php

namespace Tests\Feature;

use Tests\TestCase;

class ThemePresetsTest extends TestCase
{
    /**
     * Verify that presets.json exists and is valid JSON with all 4 presets.
     */
    public function test_presets_json_exists_and_has_all_presets(): void
    {
        $jsonPath = base_path('resources/themes/presets.json');
        $this->assertFileExists($jsonPath);

        $presets = json_decode(file_get_contents($jsonPath), true);
        $this->assertNotNull($presets, 'presets.json must be valid JSON');

        $requiredPresets = ['royal', 'ocean', 'emerald', 'midnight'];
        foreach ($requiredPresets as $preset) {
            $this->assertArrayHasKey($preset, $presets, "Missing preset: {$preset}");
            $this->assertArrayHasKey('primary', $presets[$preset], "Preset {$preset} missing 'primary'");
            $this->assertArrayHasKey('sidebar', $presets[$preset], "Preset {$preset} missing 'sidebar'");
            $this->assertArrayHasKey('on_primary', $presets[$preset], "Preset {$preset} missing 'on_primary'");
            $this->assertArrayHasKey('text', $presets[$preset], "Preset {$preset} missing 'text'");
            $this->assertArrayHasKey('surface', $presets[$preset], "Preset {$preset} missing 'surface'");
            $this->assertArrayHasKey('id', $presets[$preset], "Preset {$preset} missing 'id'");
            $this->assertArrayHasKey('label', $presets[$preset], "Preset {$preset} missing 'label'");
        }
    }

    /**
     * Verify that build_theme_css.cjs --check passes (generated CSS matches presets.json).
     */
    public function test_generated_css_matches_presets_json(): void
    {
        $rootDir = realpath(base_path('../../'));
        $script = $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'build_theme_css.cjs';

        if (!file_exists($script)) {
            $this->markTestSkipped('build_theme_css.cjs not found at: ' . $script);
        }

        $output = [];
        $exitCode = 0;
        exec("node " . escapeshellarg($script) . " --check 2>&1", $output, $exitCode);

        $this->assertEquals(0, $exitCode, "build_theme_css.cjs --check failed:\n" . implode("\n", $output));
    }

    /**
     * Verify that verify_theme_contrast.cjs passes (all WCAG AA >= 4.5:1).
     */
    public function test_all_presets_pass_wcag_aa_contrast(): void
    {
        $rootDir = realpath(base_path('../../'));
        $script = $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'verify_theme_contrast.cjs';

        if (!file_exists($script)) {
            $this->markTestSkipped('verify_theme_contrast.cjs not found at: ' . $script);
        }

        $output = [];
        $exitCode = 0;
        exec("node " . escapeshellarg($script) . " 2>&1", $output, $exitCode);

        $this->assertEquals(0, $exitCode, "verify_theme_contrast.cjs failed:\n" . implode("\n", $output));
        $this->assertStringContainsString('SUCCESS', implode("\n", $output));
    }

    /**
     * Verify tit-theme.css exists in admin-theme/css/ and contains token definitions.
     */
    public function test_tit_theme_css_exists_and_contains_tokens(): void
    {
        $cssPath = public_path('admin-theme/css/tit-theme.css');
        $this->assertFileExists($cssPath);

        $content = file_get_contents($cssPath);
        $this->assertStringContainsString('--t-primary:', $content);
        $this->assertStringContainsString('--t-sidebar:', $content);
        $this->assertStringContainsString('--t-on-primary:', $content);
        $this->assertStringContainsString('data-theme-preset', $content);
        $this->assertStringContainsString('.btn-primary', $content);
        $this->assertStringContainsString('.card-header', $content);
    }

    /**
     * Verify the admin layout includes tit-theme.css and data-theme-preset attribute.
     */
    public function test_admin_layout_includes_theme_css_and_preset_attribute(): void
    {
        $layoutPath = resource_path('views/layouts/admin.blade.php');
        $this->assertFileExists($layoutPath);

        $content = file_get_contents($layoutPath);
        $this->assertStringContainsString('tit-theme.css', $content, 'Layout must include tit-theme.css');
        $this->assertStringContainsString('data-theme-preset', $content, 'Layout must have data-theme-preset attribute');
    }

    /**
     * Verify that #3b3363 (old hardcoded purple header color) is gone from admin views.
     */
    public function test_no_hardcoded_3b3363_in_admin_views(): void
    {
        $viewsDir = resource_path('views/admin');
        $this->assertDirectoryExists($viewsDir);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsDir, \FilesystemIterator::SKIP_DOTS)
        );

        $violations = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            $content = file_get_contents($file->getPathname());
            if (stripos($content, '#3b3363') !== false) {
                $violations[] = str_replace($viewsDir, '', $file->getPathname());
            }
        }

        $this->assertEmpty($violations, "Files still contain #3b3363:\n" . implode("\n", $violations));
    }
}

