<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require dirname(__DIR__) . '/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'null',
            'services.gemini.api_key' => '',
            'services.zoom.client_id' => '',
            'services.zoom.client_secret' => '',
            'services.zoom.account_id' => '',
            'services.zoom.host_email' => '',
            'services.recaptcha.site_key' => '',
            'services.recaptcha.secret_key' => '',
            'services.google.client_id' => '',
            'services.google.client_secret' => '',
            'services.google.redirect' => '',
            'payhere.merchant_id' => '',
            'payhere.merchant_secret' => '',
            'payhere.app_id' => '',
            'payhere.app_secret' => '',
            'services.whatsapp.token' => '',
            'services.whatsapp.phone_number_id' => '',
            'services.meta_whatsapp.token' => '',
            'services.meta_whatsapp.phone_number_id' => '',
            'mail.mailers.smtp.host' => '',
            'mail.mailers.smtp.username' => '',
            'mail.mailers.smtp.password' => '',
        ]);

        $safeEmptyEnv = [
            'GEMINI_API_KEY',
            'ZOOM_CLIENT_ID', 'ZOOM_CLIENT_SECRET', 'ZOOM_ACCOUNT_ID', 'ZOOM_HOST_EMAIL',
            'RECAPTCHA_SITE_KEY', 'RECAPTCHA_SECRET_KEY',
            'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI',
            'PAYHERE_MERCHANT_ID', 'PAYHERE_MERCHANT_SECRET', 'PAYHERE_APP_ID', 'PAYHERE_APP_SECRET',
            'WHATSAPP_TOKEN', 'WHATSAPP_PHONE_NUMBER_ID',
            'META_WHATSAPP_TOKEN', 'META_WHATSAPP_PHONE_NUMBER_ID',
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        ];
        foreach ($safeEmptyEnv as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }
}
