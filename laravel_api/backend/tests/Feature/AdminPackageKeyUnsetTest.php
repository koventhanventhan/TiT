<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Package;
use App\Models\Subject;
use App\Models\User;
use App\Services\GeminiTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPackageKeyUnsetTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'name' => 'Unset Key Test Institute',
            'slug' => 'unset-inst',
            'domain' => 'unset.localhost',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'email' => 'admin-unset@example.com',
        ]);

        GeminiTranslationService::resetLoggedUnconfigured();
    }

    public function test_post_admin_packages_empty_body_returns_validation_error_with_key_unset(): void
    {
        config(['services.gemini.api_key' => null]);
        unset($_ENV['GEMINI_API_KEY'], $_SERVER['GEMINI_API_KEY']);
        putenv('GEMINI_API_KEY');

        $this->actingAs($this->admin);

        $response = $this->withHeaders([
            'Host' => 'unset.localhost',
        ])->post('/admin/packages', []);

        $this->assertTrue(in_array($response->getStatusCode(), [302, 422]));
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    public function test_post_admin_packages_valid_body_succeeds_without_key(): void
    {
        config(['services.gemini.api_key' => null]);
        unset($_ENV['GEMINI_API_KEY'], $_SERVER['GEMINI_API_KEY']);
        putenv('GEMINI_API_KEY');

        $this->actingAs($this->admin);

        $response = $this->withHeaders([
            'Host' => 'unset.localhost',
        ])->post('/admin/packages', [
            'name' => 'Combined Maths Grade 12',
            'category' => 'al',
            'medium' => 'english',
            'package_price' => 3500,
            'original_price' => 4000,
            'type' => 'fixed',
            'applicable_grades' => ['12'],
            'is_active' => '1',
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
        $this->assertDatabaseHas('packages', [
            'name' => 'Combined Maths Grade 12',
            'name_ta' => 'Combined Maths Grade 12',
            'name_si' => 'Combined Maths Grade 12',
        ]);
    }

    public function test_post_admin_subjects_empty_body_returns_validation_error_with_key_unset(): void
    {
        config(['services.gemini.api_key' => null]);
        unset($_ENV['GEMINI_API_KEY'], $_SERVER['GEMINI_API_KEY']);
        putenv('GEMINI_API_KEY');

        $this->actingAs($this->admin);

        $response = $this->withHeaders([
            'Host' => 'unset.localhost',
        ])->post('/admin/subjects', []);

        $this->assertTrue(in_array($response->getStatusCode(), [302, 422]));
        $this->assertNotEquals(500, $response->getStatusCode());
    }
}

