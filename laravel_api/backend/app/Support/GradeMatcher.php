<?php

namespace App\Support;

class GradeMatcher
{
    /**
     * Normalize grade string to its numeric core if digits are found,
     * or uppercase trimmed string otherwise.
     */
    public static function normalize(?string $grade): ?string
    {
        if ($grade === null) {
            return null;
        }

        $trimmed = trim($grade);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/(\d+)/', $trimmed, $match)) {
            return $match[1];
        }

        return strtoupper($trimmed);
    }

    /**
     * Check if two grade strings match after normalization.
     * If either grade is null or empty, returns true (matches all).
     */
    public static function matches(?string $gradeA, ?string $gradeB): bool
    {
        $normA = self::normalize($gradeA);
        $normB = self::normalize($gradeB);

        if ($normA === null || $normB === null) {
            return true;
        }

        return $normA === $normB;
    }
}
