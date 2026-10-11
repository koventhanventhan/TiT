<?php

namespace App\Services;

use RuntimeException;

/**
 * Mints Firebase "custom tokens" so the admin dashboard can sign in to
 * Firebase Auth and upload straight to Firebase Storage.
 *
 * The Storage security rules (firebase/storage.rules) only allow writes when
 * the token carries `admin: true` and the matching institute, so a random
 * visitor cannot upload to or delete from the bucket.
 *
 * No extra composer package is needed: a custom token is just an RS256 JWT
 * signed with the service-account private key.
 */
class FirebaseTokenService
{
    private const AUDIENCE = 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit';

    public function __construct(
        private ?string $clientEmail,
        private ?string $privateKey,
    ) {
        // .env files store the key with literal "\n" sequences.
        if ($this->privateKey !== null) {
            $this->privateKey = str_replace('\n', "\n", trim($this->privateKey, " \t\n\r\0\x0B\"'"));
        }
    }

    public static function fromConfig(): self
    {
        return new self(
            config('services.firebase.client_email'),
            config('services.firebase.private_key'),
        );
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientEmail) && !empty($this->privateKey);
    }

    /**
     * @param  array<string, mixed>  $claims  Extra claims visible as request.auth.token.* in the rules.
     */
    public function createCustomToken(string $uid, array $claims = [], int $ttlSeconds = 3600): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Firebase service account is not configured.');
        }

        $now = time();
        $payload = [
            'iss' => $this->clientEmail,
            'sub' => $this->clientEmail,
            'aud' => self::AUDIENCE,
            'iat' => $now,
            'exp' => $now + min($ttlSeconds, 3600), // Firebase caps custom tokens at 1 hour
            'uid' => $uid,
            'claims' => $claims,
        ];

        $segments = [
            $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64Url(json_encode($payload)),
        ];
        $signingInput = implode('.', $segments);

        $key = openssl_pkey_get_private($this->privateKey);
        if ($key === false) {
            throw new RuntimeException('Firebase private key is invalid.');
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign the Firebase token.');
        }

        return $signingInput . '.' . $this->base64Url($signature);
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
