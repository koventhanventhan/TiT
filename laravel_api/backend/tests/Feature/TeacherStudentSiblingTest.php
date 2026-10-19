<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherStudentSiblingTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_students_list_and_profile_handles_sibling_accounts_and_placeholder_emails(): void
    {
        $institute = \App\Models\Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
        ]);

        $teacher = User::factory()->create([
            'institute_id' => $institute->id,
            'role' => 'teacher',
            'name' => 'Teacher One',
            'email' => 'teacher@example.com',
        ]);

        $parent = User::factory()->create([
            'institute_id' => $institute->id,
            'role' => 'user',
            'name' => 'Ravi Parent',
            'full_name' => 'Ravindran Parent',
            'email' => 'parent@example.com',
            'phone_number' => '0771234567',
        ]);

        $sibling = User::factory()->create([
            'institute_id' => $institute->id,
            'role' => 'user',
            'parent_id' => $parent->id,
            'name' => 'kavi_sibling',
            'full_name' => 'Kavitha Sibling',
            'email' => 'sibling_' . $parent->id . '_1729000000@child.local',
            'phone_number' => null,
            'registration_status' => 'approved',
            'current_grade' => '10',
            'stream' => 'Science',
        ]);

        $standalone = User::factory()->create([
            'institute_id' => $institute->id,
            'role' => 'user',
            'parent_id' => null,
            'name' => 'amal_normal',
            'full_name' => 'Amal Normal',
            'email' => 'amal@example.com',
            'phone_number' => '0779999999',
            'registration_status' => 'payment_completed',
            'current_grade' => '11',
            'stream' => 'Commerce',
        ]);

        // 1. Test User helper methods
        $this->assertTrue($sibling->isPlaceholderEmail());
        $this->assertNull($sibling->displayEmail());
        $this->assertFalse($standalone->isPlaceholderEmail());
        $this->assertEquals('amal@example.com', $standalone->displayEmail());

        // 2. Test GET /api/teacher/students
        $response = $this->actingAs($teacher)->getJson('/api/teacher/students');
        $response->assertStatus(200);

        $students = collect($response->json());
        $siblingItem = $students->firstWhere('id', $sibling->id);
        $standaloneItem = $students->firstWhere('id', $standalone->id);

        $this->assertNotNull($siblingItem);
        $this->assertTrue($siblingItem['is_sibling']);
        $this->assertEquals('Ravindran Parent', $siblingItem['parent_name']);
        $this->assertEquals('0771234567', $siblingItem['phone_number']);
        $this->assertTrue($siblingItem['phone_is_parent']);
        $this->assertNull($siblingItem['email']);
        $this->assertEquals('Kavitha Sibling', $siblingItem['display_name']);

        $this->assertNotNull($standaloneItem);
        $this->assertFalse($standaloneItem['is_sibling']);
        $this->assertNull($standaloneItem['parent_name']);
        $this->assertEquals('0779999999', $standaloneItem['phone_number']);
        $this->assertFalse($standaloneItem['phone_is_parent']);
        $this->assertEquals('amal@example.com', $standaloneItem['email']);

        // 3. Test GET /api/teacher/students/{sibling->id}
        $showResponse = $this->actingAs($teacher)->getJson("/api/teacher/students/{$sibling->id}");
        $showResponse->assertStatus(200);

        $profile = $showResponse->json('profile');
        $this->assertEquals($sibling->id, $profile['id']);
        $this->assertEquals('Kavitha Sibling', $profile['name']);
        $this->assertEquals('Kavitha Sibling', $profile['full_name']);
        $this->assertTrue($profile['is_sibling']);
        $this->assertEquals('Ravindran Parent', $profile['parent_name']);
        $this->assertEquals($parent->id, $profile['parent_id']);
        $this->assertEquals('0771234567', $profile['phone_number']);
        $this->assertTrue($profile['phone_is_parent']);
        $this->assertNull($profile['email']);
    }
}
