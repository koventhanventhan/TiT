<?php

namespace App\Support;

class Redact
{
    /**
     * Exact or substring key fragments considered sensitive in structured payloads/logs.
     *
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'md5sig',
        'merchant_secret',
        'app_secret',
        'client_secret',
        'mail_password',
        'smtp_password',
        'access_token',
        'refresh_token',
        'private_key',
        'api_key',
        'secret',
        'password',
        'passwd',
        'token',
        'authorization',
        'credentials',
    ];

    /**
     * Mask a single secret value to `••••` + last 4 characters only.
     * Never exposes the full value (values <= 4 chars return `••••`).
     */
    public static function maskValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $len = mb_strlen($value, 'UTF-8');
        if ($len <= 4) {
            return '••••';
        }

        return '••••' . mb_substr($value, -4, 4, 'UTF-8');
    }

    /**
     * Recursively redact sensitive keys and bearer/secret patterns in an array.
     *
     * @param array<mixed, mixed> $data
     * @param array<int, string> $extraSecrets
     * @return array<mixed, mixed>
     */
    public static function array(array $data, array $extraSecrets = []): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                $result[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::array($value, $extraSecrets);
                continue;
            }

            if (is_string($value)) {
                $result[$key] = self::scrubInlinePatterns($value, $extraSecrets);
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Sanitise an error/exception message so no credentials, tokens, hashes, or passwords remain.
     *
     * @param string|null $text
     * @param array<int, string> $extraSecrets
     */
    public static function sanitizeText(?string $text, array $extraSecrets = []): ?string
    {
        if ($text === null) {
            return null;
        }

        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        $clean = self::scrubInlinePatterns($trimmed, $extraSecrets);

        if (mb_strlen($clean, 'UTF-8') > 1000) {
            $clean = mb_substr($clean, 0, 1000, 'UTF-8') . '…';
        }

        return $clean;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(trim($key));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        foreach (self::SENSITIVE_KEYS as $needle) {
            if ($normalized === $needle || str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $extraSecrets
     */
    private static function scrubInlinePatterns(string $text, array $extraSecrets = []): string
    {
        $clean = $text;

        foreach ($extraSecrets as $secret) {
            if (is_string($secret) && strlen($secret) >= 3) {
                $clean = str_replace($secret, '[REDACTED]', $clean);
            }
        }

        // Scrub URL userinfo credentials e.g. smtp://user:pass@host
        $clean = (string) preg_replace('#(://[^/\s:@]+:)[^@\s]+@#i', '$1[REDACTED]@', $clean);

        // Scrub Authorization Bearer tokens
        $clean = (string) preg_replace('/\bBearer\s+[A-Za-z0-9._\-~+\/=]+/i', 'Bearer [REDACTED]', $clean);

        // Scrub Meta Graph access tokens (EAA...)
        $clean = (string) preg_replace('/\bEAA[A-Za-z0-9]{12,}\b/', '[REDACTED]', $clean);

        // Scrub inline key=value or "key":"value" secret pairs
        $clean = (string) preg_replace(
            '/\b(md5sig|merchant_secret|app_secret|client_secret|access_token|api_key|private_key|mail_password|password|secret|token)\b(\s*["\']?\s*[:=]\s*["\']?)([^\s,&"\'}\]]+)/i',
            '$1$2[REDACTED]',
            $clean
        );

        return $clean;
    }
}
