<?php

namespace Tests\Feature;

use App\Services\Rag\DistressDetector;
use Tests\TestCase;

class HelplineConfigurationTest extends TestCase
{
    public function test_default_config_has_no_hardcoded_helpline_numbers_and_no_1990(): void
    {
        // Unset env vars temporarily to inspect fallback defaults in config/rag.php
        $vars = [
            'RAG_HELPLINE_PRIMARY_NAME',
            'RAG_HELPLINE_PRIMARY_NUMBER',
            'RAG_HELPLINE_PRIMARY_HOURS',
            'RAG_HELPLINE_SECONDARY_NAME',
            'RAG_HELPLINE_SECONDARY_NUMBER',
            'RAG_HELPLINE_SECONDARY_HOURS',
            'RAG_HELPLINE_INSTITUTE_NUMBER',
            'RAG_HELPLINE_INSTITUTE_HOURS',
        ];

        $backups = [];
        foreach ($vars as $var) {
            $backups[$var] = getenv($var);
            putenv($var);
            unset($_ENV[$var], $_SERVER[$var]);
        }

        try {
            $rawConfig = require config_path('rag.php');
            $helplines = $rawConfig['safety']['helplines'] ?? [];

            $this->assertNotEmpty($helplines, 'Safety helplines array must exist in config/rag.php');

            foreach ($helplines as $line) {
                $number = $line['number'];
                $this->assertSame('', $number, "Helpline number fallback must be empty string, never hardcoded, got: '{$number}'");
                $this->assertNotEquals('1990', $number, 'Helpline number must never default to 1990');
            }
        } finally {
            foreach ($backups as $var => $val) {
                if ($val !== false) {
                    putenv("{$var}={$val}");
                    $_ENV[$var] = $val;
                    $_SERVER[$var] = $val;
                }
            }
        }
    }

    public function test_caring_message_with_empty_config_contains_no_helpline_bullets_or_1990(): void
    {
        config(['rag.safety.helplines' => [
            ['name' => 'Primary', 'number' => '', 'hours' => ''],
            ['name' => 'Secondary', 'number' => '', 'hours' => ''],
            ['name' => 'Institute Support', 'number' => '', 'hours' => ''],
        ]]);

        $detector = new DistressDetector();

        foreach (['en', 'ta', 'si'] as $lang) {
            $msg = $detector->getCaringMessage($lang);
            $this->assertStringNotContainsString('1990', $msg, "Language {$lang} must not contain 1990");
            $this->assertStringNotContainsString('•', $msg, "Language {$lang} must not contain helpline bullets when config is empty");
        }
    }

    public function test_caring_message_renders_strictly_configured_helplines_from_config(): void
    {
        config(['rag.safety.helplines' => [
            [
                'name' => 'Configured Crisis Helpline',
                'number' => '9999-VERIFIED',
                'hours' => '24/7',
            ],
            [
                'name' => 'Empty Slot',
                'number' => '',
                'hours' => '',
            ],
        ]]);

        $detector = new DistressDetector();
        $msg = $detector->getCaringMessage('en');

        $this->assertStringContainsString('• Configured Crisis Helpline: 9999-VERIFIED (24/7)', $msg);
        $this->assertStringNotContainsString('Empty Slot', $msg);
        $this->assertStringNotContainsString('1990', $msg);
    }

    public function test_institute_contact_is_injected_dynamically_without_hardcoded_defaults(): void
    {
        config(['rag.safety.helplines' => [
            [
                'name' => 'Institute / Teacher Support',
                'number' => '',
                'hours' => '8am-5pm',
            ],
        ]]);

        $detector = new DistressDetector();
        $msg = $detector->getCaringMessage('en', '+94 21 222 3333');

        $this->assertStringContainsString('• Institute / Teacher Support: +94 21 222 3333 (8am-5pm)', $msg);
        $this->assertStringNotContainsString('1990', $msg);
    }
}
