<?php

namespace Tests\Unit;

use App\Services\FirebaseTokenService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FirebaseTokenServiceTest extends TestCase
{
    private string $privateKey;
    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $possibleConfs = [
            getenv('OPENSSL_CONF') ?: null,
            'C:/xampp/apache/conf/openssl.cnf',
            'C:/xampp/php/extras/ssl/openssl.cnf',
        ];
        foreach ($possibleConfs as $conf) {
            if ($conf && file_exists($conf)) {
                $config['config'] = $conf;
                break;
            }
        }

        $res = openssl_pkey_new($config);
        $privateKey = '';
        openssl_pkey_export($res, $privateKey, null, $config);
        $this->privateKey = $privateKey;
        $details = openssl_pkey_get_details($res);
        $this->publicKey = $details['key'];
    }

    public function test_throws_when_unconfigured(): void
    {
        $service = new FirebaseTokenService(null, null);
        $this->assertFalse($service->isConfigured());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Firebase service account is not configured.');
        $service->createCustomToken('user-1');
    }

    public function test_throws_when_private_key_is_invalid(): void
    {
        $service = new FirebaseTokenService('test@example.iam.gserviceaccount.com', 'invalid-key');
        $this->assertTrue($service->isConfigured());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Firebase private key is invalid.');
        $service->createCustomToken('user-1');
    }

    public function test_mints_and_verifies_custom_token_with_claims_and_capped_ttl(): void
    {
        // Test literal \n escaping handled
        $escapedKey = str_replace("\n", '\n', $this->privateKey);
        $clientEmail = 'tit-service@titeducation-ff12f.iam.gserviceaccount.com';

        $service = new FirebaseTokenService($clientEmail, $escapedKey);
        $this->assertTrue($service->isConfigured());

        $uid = 'admin-42';
        $claims = [
            'admin' => true,
            'institute' => 'i1',
        ];

        // Request 7200s TTL -> must be capped at 3600s
        $token = $service->createCustomToken($uid, $claims, 7200);
        $this->assertIsString($token);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        [$headerB64, $payloadB64, $sigB64] = $parts;

        // Verify header
        $headerJson = base64_decode(strtr($headerB64, '-_', '+/'));
        $header = json_decode($headerJson, true);
        $this->assertSame(['alg' => 'RS256', 'typ' => 'JWT'], $header);

        // Verify payload
        $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'));
        $payload = json_decode($payloadJson, true);

        $this->assertSame($clientEmail, $payload['iss']);
        $this->assertSame($clientEmail, $payload['sub']);
        $this->assertSame('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit', $payload['aud']);
        $this->assertSame($uid, $payload['uid']);
        $this->assertSame($claims, $payload['claims']);
        $this->assertTrue($payload['claims']['admin']);
        $this->assertSame('i1', $payload['claims']['institute']);
        $this->assertLessThanOrEqual(3600, $payload['exp'] - $payload['iat']);

        // Verify signature with public key
        $signingInput = $headerB64 . '.' . $payloadB64;
        $signature = base64_decode(strtr($sigB64, '-_', '+/'));
        $pubKeyRes = openssl_pkey_get_public($this->publicKey);
        $verified = openssl_verify($signingInput, $signature, $pubKeyRes, OPENSSL_ALGO_SHA256);

        $this->assertSame(1, $verified, 'Token signature verification failed.');
    }
}
