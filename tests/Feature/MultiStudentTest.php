<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class MultiStudentTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_profile_context_middleware_switches_user()
    {
        $institute = \App\Models\Institute::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'domain' => 'test',
            'status' => 'active',
        ]);

        // 1. Create parent and sibling
        $parent = clone User::factory()->create(['role' => 'user', 'institute_id' => $institute->id]);
        $sibling = clone User::factory()->create([
            'role' => 'user',
            'parent_id' => $parent->id,
            'institute_id' => $institute->id
        ]);

        // 2. Authenticate as parent
        \Laravel\Sanctum\Sanctum::actingAs($parent);

        // 3. Request without X-Profile-Id should return parent data
        $responseParent = $this->getJson('/api/auth/user');
        if ($responseParent->status() !== 200) {
            dump($responseParent->getContent());
        }
        $responseParent->assertStatus(200);
        $this->assertEquals($parent->id, $responseParent->json('user.id'));

        // 4. Request with X-Profile-Id should return sibling data
        $responseSibling = $this->getJson('/api/auth/user', [
            'X-Profile-Id' => $sibling->id
        ]);
        $responseSibling->assertStatus(200);
        $this->assertEquals($sibling->id, $responseSibling->json('user.id'));
    }

    public function test_payment_status_is_scoped_to_active_profile()
    {
        $institute = \App\Models\Institute::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute-2',
            'domain' => 'test2',
            'status' => 'active',
        ]);

        $parent = clone User::factory()->create(['role' => 'user', 'institute_id' => $institute->id]);
        $sibling = clone User::factory()->create([
            'role' => 'user',
            'parent_id' => $parent->id,
            'institute_id' => $institute->id
        ]);

        // Disable FK constraints for SQLite tests
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        // Create a payment for sibling only
        \App\Models\Payment::create([
            'user_id' => $sibling->id,
            'amount' => 500,
            'status' => 'paid',
            'payment_method' => 'online',
            'year_month' => now()->format('Y-m'),
            'gateway_ref' => 'PH_TEST_123',
            'institute_id' => $institute->id,
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($parent);

        // Parent should not be paid
        $responseParent = $this->getJson('/api/student/payment-status');
        if ($responseParent->status() !== 200) {
            dump($responseParent->getContent());
        }
        $responseParent->assertStatus(200);
        $this->assertFalse($responseParent->json('is_paid'));

        // Sibling should be paid
        $responseSibling = $this->getJson('/api/student/payment-status', [
            'X-Profile-Id' => $sibling->id
        ]);
        $responseSibling->assertStatus(200);
        $this->assertTrue($responseSibling->json('is_paid'));
    }
}
