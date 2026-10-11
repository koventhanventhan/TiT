<?php

namespace App\Services;

class FirebaseStorageUrlValidator
{
    /**
     * Validate a Firebase Storage URL for a specific institute.
     *
     * @param string $url
     * @param int $instituteId
     * @param string|null $configuredBucket Optional override for testing
     * @return bool
     */
    public static function isValidForInstitute(string $url, int $instituteId, ?string $configuredBucket = null): bool
    {
        return self::validate($url, $instituteId, $configuredBucket);
    }

    /**
     * Validate a Firebase Storage URL against configured project bucket and path structure.
     *
     * @param string $url
     * @param int|null $instituteId
     * @param string|null $configuredBucket Optional override for testing
     * @return bool
     */
    public static function validate(string $url, ?int $instituteId = null, ?string $configuredBucket = null): bool
    {
        $expectedBucket = $configuredBucket ?? config('services.firebase.web.storageBucket');
        if (empty($expectedBucket) || !is_string($expectedBucket) || trim($expectedBucket) === '') {
            return false; // Fail closed if storageBucket config is empty
        }

        $parts = parse_url($url);
        if (!$parts || !is_array($parts)) {
            return false;
        }

        // Scheme must be https, host exactly firebasestorage.googleapis.com, no userinfo/port
        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        if (($parts['host'] ?? '') !== 'firebasestorage.googleapis.com') {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return false;
        }

        // Path must match #^/v0/b/([^/]+)/o/(.+)$#
        $path = $parts['path'] ?? '';
        if (!preg_match('#^/v0/b/([^/]+)/o/(.+)$#', $path, $matches)) {
            return false;
        }

        // Captured bucket must equal configured storageBucket
        $capturedBucket = $matches[1];
        if ($capturedBucket !== $expectedBucket) {
            return false;
        }

        // Urldecode group 2 object path: must not contain '..'
        $objectPath = urldecode($matches[2]);
        if (str_contains($objectPath, '..')) {
            return false;
        }

        // If institute_id is provided, must start with learning-suite/i{institute_id}/
        if ($instituteId !== null) {
            $expectedPrefix = "learning-suite/i{$instituteId}/";
            if (!str_starts_with($objectPath, $expectedPrefix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate whether file_path is a relative path or an https://firebasestorage.googleapis.com/ URL on the configured bucket.
     *
     * @param string|null $value
     * @param string|null $configuredBucket Optional override for testing
     * @return bool
     */
    public static function isValidFilePathOrUrl(?string $value, ?string $configuredBucket = null): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        // Relative path: no '..', no leading '/', no backslashes
        if (preg_match('/^(?!.*\.\.)(?!\/)[A-Za-z0-9_\-.\/ ]+$/', $value) && !str_contains($value, '\\')) {
            return true;
        }

        // Firebase Storage URL on the configured bucket
        return self::validate($value, null, $configuredBucket);
    }
}
