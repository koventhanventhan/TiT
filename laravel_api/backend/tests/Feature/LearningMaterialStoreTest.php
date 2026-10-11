<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningMaterialStoreTest extends TestCase
{
    use RefreshDatabase;

    private $institute;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $this->institute = \App\Models\Institute::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_store_material_with_firebase_url(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $firebaseUrl = "https://firebasestorage.googleapis.com/v0/b/titeducation-ff12f.firebasestorage.app/o/learning-suite%2Fi{$this->institute->id}%2Fnotes%2Fgrade-4%2F123_notes.pdf?alt=media&token=test-token";

        $response = $this->actingAs($admin, 'web')->post('/admin/settings/learning/store', [
            'title' => 'Grade 4 Science Notes',
            'type' => 'note',
            'grade' => 'grade-4',
            'file_url' => $firebaseUrl,
            'file_size' => '3.5 MB',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('learning_materials', [
            'title' => 'Grade 4 Science Notes',
            'type' => 'note',
            'grade' => 'grade-4',
            'file_path' => $firebaseUrl,
            'file_size' => '3.5 MB',
        ]);
    }

    public function test_admin_cannot_store_firebase_url_for_different_institute(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        // URL contains i999 instead of current institute
        $firebaseUrl = 'https://firebasestorage.googleapis.com/v0/b/titeducation-ff12f.firebasestorage.app/o/learning-suite%2Fi999%2Fnotes%2Fgrade-4%2F123_notes.pdf?alt=media&token=test-token';

        $response = $this->actingAs($admin, 'web')->post('/admin/settings/learning/store', [
            'title' => 'Grade 4 Science Notes',
            'type' => 'note',
            'grade' => 'grade-4',
            'file_url' => $firebaseUrl,
            'file_size' => '3.5 MB',
        ]);

        $response->assertSessionHasErrors(['file_url']);
    }

    public function test_delete_material_with_firebase_url_deletes_db_row_without_error(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $firebaseUrl = "https://firebasestorage.googleapis.com/v0/b/titeducation-ff12f.firebasestorage.app/o/learning-suite%2Fi{$this->institute->id}%2Fnotes%2Fgrade-4%2F123_notes.pdf?alt=media&token=test-token";

        $material = LearningMaterial::create([
            'title' => 'Delete Test Note',
            'type' => 'note',
            'grade' => 'grade-4',
            'file_path' => $firebaseUrl,
            'file_size' => '1.2 MB',
        ]);

        $response = $this->actingAs($admin, 'web')->delete("/admin/settings/learning/{$material->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('learning_materials', [
            'id' => $material->id,
        ]);
    }
}
