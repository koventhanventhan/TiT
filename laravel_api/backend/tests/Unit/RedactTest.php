<?php

namespace Tests\Unit;

use App\Support\Redact;
use PHPUnit\Framework\TestCase;

class RedactTest extends TestCase
{
    public function test_mask_value_shows_only_four_dots_and_last_four_characters(): void
    {
        $this->assertSame('', Redact::maskValue(null));
        $this->assertSame('', Redact::maskValue(''));
        $this->assertSame('••••', Redact::maskValue('a'));
        $this->assertSame('••••', Redact::maskValue('abcd'));
        $this->assertSame('••••5678', Redact::maskValue('12345678'));
        $this->assertSame('••••Z9Q1', Redact::maskValue('MERCHANT_SECRET_VALUE_Z9Q1'));
    }

    public function test_array_recursively_redacts_md5sig_tokens_passwords_and_secrets(): void
    {
        $raw = [
            'merchant_id' => '1228450',
            'order_id' => 'ORD_1001',
            'payhere_amount' => '2500.00',
            'md5sig' => 'ABCDEF0123456789ABCDEF0123456789',
            'merchant_secret' => 'MY_PAYHERE_MERCHANT_SECRET',
            'password' => 'SuperSecretPass!123',
            'new_password_confirmation' => 'SuperSecretPass!123',
            '_token' => 'csrf_token_value',
            'nested' => [
                'access_token' => 'EAAGraphMetaToken999',
                'client_secret' => 'zoom_client_secret_xyz',
                'Authorization' => 'Bearer EAAGraphMetaToken999',
                'note' => 'Connection failed with Bearer EAAGraphMetaToken999 on https://user:smtpPass99@smtp.example.com:587',
                'safe_field' => 'visible-value',
            ],
        ];

        $redacted = Redact::array($raw, ['EXTRA_RAW_SECRET_555']);

        $this->assertSame('1228450', $redacted['merchant_id']);
        $this->assertSame('ORD_1001', $redacted['order_id']);
        $this->assertSame('2500.00', $redacted['payhere_amount']);
        $this->assertSame('[REDACTED]', $redacted['md5sig']);
        $this->assertSame('[REDACTED]', $redacted['merchant_secret']);
        $this->assertSame('[REDACTED]', $redacted['password']);
        $this->assertSame('[REDACTED]', $redacted['new_password_confirmation']);
        $this->assertSame('[REDACTED]', $redacted['_token']);
        $this->assertSame('[REDACTED]', $redacted['nested']['access_token']);
        $this->assertSame('[REDACTED]', $redacted['nested']['client_secret']);
        $this->assertSame('[REDACTED]', $redacted['nested']['Authorization']);
        $this->assertSame('visible-value', $redacted['nested']['safe_field']);

        $serialized = json_encode($redacted);
        $this->assertStringNotContainsString('ABCDEF0123456789ABCDEF0123456789', (string) $serialized);
        $this->assertStringNotContainsString('MY_PAYHERE_MERCHANT_SECRET', (string) $serialized);
        $this->assertStringNotContainsString('SuperSecretPass!123', (string) $serialized);
        $this->assertStringNotContainsString('EAAGraphMetaToken999', (string) $serialized);
        $this->assertStringNotContainsString('zoom_client_secret_xyz', (string) $serialized);
        $this->assertStringNotContainsString('smtpPass99', (string) $serialized);
    }

    public function test_sanitize_text_scrubs_bearer_tokens_url_passwords_and_extra_secrets(): void
    {
        $rawError = 'SMTP error for smtp://billing:MySmtpSecret888@mail.school.lk:465 with Bearer EAAWhatsappSecret777 and key CUSTOM_KEY_123456';
        $clean = Redact::sanitizeText($rawError, ['CUSTOM_KEY_123456']);

        $this->assertNotNull($clean);
        $this->assertStringNotContainsString('MySmtpSecret888', $clean);
        $this->assertStringNotContainsString('EAAWhatsappSecret777', $clean);
        $this->assertStringNotContainsString('CUSTOM_KEY_123456', $clean);
    }

    public function test_sanitize_text_and_array_scrub_gemini_recaptcha_and_google_keys(): void
    {
        $geminiKey = 'AIzaSyD-1234567890abcdefghijklmnopqrstuv';
        $recaptchaSecret = '6Ld_dummySecretKeyWith38CharactersLong1234';
        $googleSecret = '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7890';

        $rawText = "Failed calling Gemini with key {$geminiKey}, recaptcha secret={$recaptchaSecret}, google client secret: {$googleSecret}";
        $sanitized = Redact::sanitizeText($rawText);

        $this->assertStringNotContainsString($geminiKey, $sanitized);
        $this->assertStringNotContainsString($recaptchaSecret, $sanitized);
        $this->assertStringNotContainsString($googleSecret, $sanitized);
        $this->assertStringContainsString('[REDACTED]', $sanitized);

        $arrayData = [
            'gemini' => [
                'api_key' => $geminiKey,
            ],
            'recaptcha' => [
                'site_key' => '6Ld_publicSiteKey123',
                'secret_key' => $recaptchaSecret,
            ],
            'google' => [
                'client_id' => '12345.apps.googleusercontent.com',
                'client_secret' => $googleSecret,
            ],
        ];

        $redactedArray = Redact::array($arrayData);
        $this->assertSame('[REDACTED]', $redactedArray['gemini']['api_key']);
        $this->assertSame('[REDACTED]', $redactedArray['recaptcha']['secret_key']);
        $this->assertSame('6Ld_publicSiteKey123', $redactedArray['recaptcha']['site_key']);
        $this->assertSame('[REDACTED]', $redactedArray['google']['client_secret']);
        $this->assertSame('12345.apps.googleusercontent.com', $redactedArray['google']['client_id']);
    }
}
