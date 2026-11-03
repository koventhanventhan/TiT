<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlashToastTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
            'index_prefix' => 'TiT',
            'index_seq_start' => 100,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => 1,
            'email' => 'admin@tit.lk',
        ]);
    }

    public function test_flash_success_renders_toast_payload_exactly_once_on_representative_pages(): void
    {
        $routes = [
            '/admin/dashboard',
            '/admin/students',
            '/admin/teachers',
            '/admin/subjects',
            '/admin/packages',
        ];

        $uniqueSuccess = 'Flash-Success-Token-789XYZ';

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)
                ->withSession(['success' => $uniqueSuccess])
                ->get($route);

            $response->assertStatus(200);
            $content = $response->getContent();

            // Must contain the message exactly once across the whole page (no duplicate alert alert-success)
            $count = substr_count($content, $uniqueSuccess);
            $this->assertSame(
                1,
                $count,
                "Expected flash message '{$uniqueSuccess}' to appear exactly once on '{$route}', found {$count} times."
            );

            // Must NOT render as legacy alert-success block
            $this->assertStringNotContainsString(
                'alert alert-success',
                $content,
                "Route '{$route}' should not contain duplicate legacy alert alert-success banner."
            );

            // Must be rendered by the flash toast container
            $this->assertStringContainsString('data-flash-messages=', $content);
            $this->assertStringContainsString('tit-notify.js', $content);
        }
    }
}
