<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FirebaseTokenEndpointTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/firebase-token');
        $response->assertRedirect('/admin/login');
    }

    public function test_non_admin_user_gets_403_forbidden(): void
    {
        $student = new User([
            'id' => 999,
            'name' => 'Student User',
            'email' => 'student@example.com',
            'role' => 'student',
        ]);

        $response = $this->actingAs($student, 'web')->get('/admin/firebase-token');
        $response->assertStatus(403);
    }

    public function test_teacher_user_gets_403_forbidden(): void
    {
        $teacher = new User([
            'id' => 998,
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'role' => 'teacher',
        ]);

        $response = $this->actingAs($teacher, 'web')->get('/admin/firebase-token');
        $response->assertStatus(403);
    }

    public function test_admin_gets_503_when_firebase_unconfigured(): void
    {
        Config::set('services.firebase.client_email', null);
        Config::set('services.firebase.private_key', null);

        $admin = new User([
            'id' => 101,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'institute_id' => 1,
        ]);

        $response = $this->actingAs($admin, 'web')->getJson('/admin/firebase-token');
        $response->assertStatus(503)
            ->assertJson([
                'message' => 'Firebase service account is not configured.',
            ]);
    }

    public function test_admin_gets_200_and_token_when_configured(): void
    {
        // Generate an RSA key
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

        Config::set('services.firebase.client_email', 'test@firebase.iam.gserviceaccount.com');
        Config::set('services.firebase.private_key', $privateKey);
        Config::set('services.firebase.web', [
            'apiKey' => 'dummy-api-key',
            'projectId' => 'test-project',
        ]);

        $admin = new User([
            'id' => 102,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'institute_id' => 2,
        ]);

        $response = $this->actingAs($admin, 'web')->get('/admin/firebase-token');
        $response->assertStatus(200)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonStructure([
                'token',
                'institute_id',
                'web_config',
            ])
            ->assertJson([
                'institute_id' => 2,
                'web_config' => [
                    'apiKey' => 'dummy-api-key',
                    'projectId' => 'test-project',
                ],
            ]);
    }
}
