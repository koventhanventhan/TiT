<?php

namespace App\Services\Rag;

class LanguageDetector
{
    /**
     * Detect language of the query by analyzing Unicode script character distributions.
     * Supports:
     * - 'ta' (Tamil: U+0B80 - U+0BFF)
     * - 'si' (Sinhala: U+0D80 - U+0DFF)
     * - 'en' (English / Latin alphabet default)
     *
     * @param string $text
     * @return string 'ta'|'si'|'en'
     */
    public function detect(string $text): string
    {
        $clean = trim($text);
        if (empty($clean)) {
            return 'en';
        }

        // Count Tamil Unicode codepoints: U+0B80 - U+0BFF
        $tamilMatches = [];
        preg_match_all('/[\x{0B80}-\x{0BFF}]/u', $clean, $tamilMatches);
        $tamilCount = count($tamilMatches[0] ?? []);

        // Count Sinhala Unicode codepoints: U+0D80 - U+0DFF
        $sinhalaMatches = [];
        preg_match_all('/[\x{0D80}-\x{0DFF}]/u', $clean, $sinhalaMatches);
        $sinhalaCount = count($sinhalaMatches[0] ?? []);

        if ($tamilCount > 0 && $tamilCount >= $sinhalaCount) {
            return 'ta';
        }

        if ($sinhalaCount > 0 && $sinhalaCount > $tamilCount) {
            return 'si';
        }

        return 'en';
    }

    /**
     * Get human-readable language label.
     */
    public function getLanguageName(string $code): string
    {
        return match ($code) {
            'ta' => 'Tamil',
            'si' => 'Sinhala',
            default => 'English',
        };
    }
}
