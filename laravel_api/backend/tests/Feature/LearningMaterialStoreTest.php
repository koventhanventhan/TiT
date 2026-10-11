<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LearningMaterialStoreTest extends TestCase
{
    use RefreshDatabase;

    private $institute;
    private const TEST_BUCKET = 'titeducation-ff12f.firebasestorage.app';

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Config::set('services.firebase.web.storageBucket', self::TEST_BUCKET);
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

        $firebaseUrl = "https://firebasestorage.googleapis.com/v0/b/" . self::TEST_BUCKET . "/o/learning-suite%2Fi{$this->institute->id}%2Fnotes%2Fgrade-4%2F123_notes.pdf?alt=media&token=test-token";

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
        $firebaseUrl = 'https://firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi999%2Fnotes%2Fgrade-4%2F123_notes.pdf?alt=media&token=test-token';

        $response = $this->actingAs($admin, 'web')->post('/admin/settings/learning/store', [
            'title' => 'Grade 4 Science Notes',
            'type' => 'note',
            'grade' => 'grade-4',
            'file_url' => $firebaseUrl,
            'file_size' => '3.5 MB',
        ]);

        $response->assertSessionHasErrors(['file_url']);
    }

    public function test_store_material_rejects_foreign_bucket(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $foreignUrl = "https://firebasestorage.googleapis.com/v0/b/FOREIGN-PROJECT.appspot.com/o/learning-suite%2Fi{$this->institute->id}%2Fnotes%2Fgrade-4%2F123_notes.pdf?alt=media&token=test-token";

        $response = $this->actingAs($admin, 'web')->post('/admin/settings/learning/store', [
            'title' => 'Grade 4 Foreign Bucket',
            'type' => 'note',
            'grade' => 'grade-4',
            'file_url' => $foreignUrl,
        ]);

        $response->assertSessionHasErrors(['file_url']);
        $this->assertDatabaseMissing('learning_materials', [
            'title' => 'Grade 4 Foreign Bucket',
        ]);
    }

    public function test_store_material_rejects_marker_only_in_query_string(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $trickUrl = "https://firebasestorage.googleapis.com/v0/b/" . self::TEST_BUCKET . "/o/anything.pdf?alt=media&x=learning-suite/i{$this->institute->id}/";

        $response = $this->actingAs($admin, 'web')->post('/admin/settings/learning/store', [
            'title' => 'Query String Trick',
            'type' => 'note',
            'grade' => 'grade-4',
            'file_url' => $trickUrl,
        ]);

        $response->assertSessionHasErrors(['file_url']);
        $this->assertDatabaseMissing('learning_materials', [
            'title' => 'Query String Trick',
        ]);
    }

    public function test_store_material_rejects_when_bucket_config_empty(): void
    {
        Config::set('services.firebase.web.storageBucket', '');

        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $url = "https://firebasestorage.googleapis.com/v0/b/" . self::TEST_BUCKET . "/o/learning-suite%2Fi{$this->institute->id}%2Fnotes%2Fgrade-4%2F123_notes.pdf?alt=media";

        $response = $this->actingAs($admin, 'web')->post('/admin/settings/learning/store', [
            'title' => 'Unconfigured Bucket Store',
            'type' => 'note',
            'grade' => 'grade-4',
            'file_url' => $url,
        ]);

        $response->assertSessionHasErrors(['file_url']);
        $this->assertDatabaseMissing('learning_materials', [
            'title' => 'Unconfigured Bucket Store',
        ]);
    }

    public function test_store_material_json_request_with_bad_file_url_returns_422(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $badUrl = 'https://firebasestorage.googleapis.com/v0/b/SOME-OTHER-PROJECT.appspot.com/o/anything.pdf?alt=media';

        $response = $this->actingAs($admin, 'web')
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post('/admin/settings/learning/store', [
                'title' => 'JSON Bad URL Note',
                'type' => 'note',
                'grade' => 'grade-4',
                'file_url' => $badUrl,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['file_url'],
            ]);

        $this->assertDatabaseMissing('learning_materials', [
            'title' => 'JSON Bad URL Note',
        ]);
    }

    public function test_delete_material_with_firebase_url_deletes_db_row_without_error(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $firebaseUrl = "https://firebasestorage.googleapis.com/v0/b/" . self::TEST_BUCKET . "/o/learning-suite%2Fi{$this->institute->id}%2Fnotes%2Fgrade-4%2F123_notes.pdf?alt=media&token=test-token";

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

    public function test_delete_material_with_path_traversal_keeps_file_outside_uploads(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $decoyFile = base_path('victim.txt');
        file_put_contents($decoyFile, 'sensitive content');

        try {
            $material = LearningMaterial::create([
                'title' => 'Malicious Note',
                'type' => 'note',
                'grade' => 'grade-4',
                'file_path' => '../victim.txt',
            ]);

            $response = $this->actingAs($admin, 'web')->delete("/admin/settings/learning/{$material->id}");

            $response->assertRedirect();
            $this->assertFileExists($decoyFile);
            $this->assertDatabaseMissing('learning_materials', [
                'id' => $material->id,
            ]);
        } finally {
            if (file_exists($decoyFile)) {
                @unlink($decoyFile);
            }
        }
    }

    public function test_delete_material_with_normal_upload_path_removes_file(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $uploadsDir = public_path('uploads/notes');
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $testFile = $uploadsDir . '/safe_delete_test.pdf';
        file_put_contents($testFile, 'test pdf content');

        try {
            $material = LearningMaterial::create([
                'title' => 'Normal Delete Note',
                'type' => 'note',
                'grade' => 'grade-4',
                'file_path' => 'uploads/notes/safe_delete_test.pdf',
            ]);

            $response = $this->actingAs($admin, 'web')->delete("/admin/settings/learning/{$material->id}");

            $response->assertRedirect();
            $this->assertFileDoesNotExist($testFile);
            $this->assertDatabaseMissing('learning_materials', [
                'id' => $material->id,
            ]);
        } finally {
            if (file_exists($testFile)) {
                @unlink($testFile);
            }
        }
    }

    public function test_teacher_material_api_rejects_path_traversal(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute->id,
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->postJson('/api/teacher/materials', [
            'title' => 'Traversing Material',
            'type' => 'note',
            'file_path' => '../../.env',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_path']);

        $this->assertDatabaseMissing('learning_materials', [
            'title' => 'Traversing Material',
        ]);
    }

    public function test_teacher_material_api_accepts_file_upload(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute->id,
        ]);

        Sanctum::actingAs($teacher);

        $fakeFile = UploadedFile::fake()->create('lecture_notes.pdf', 100);

        $response = $this->post('/api/teacher/materials', [
            'title' => 'Uploaded Lecture Notes',
            'type' => 'note',
            'file' => $fakeFile,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('learning_materials', [
            'title' => 'Uploaded Lecture Notes',
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_master_admin_material_api_rejects_path_traversal(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/materials', [
            'title' => 'Admin Traversing Material',
            'type' => 'note',
            'file_path' => '../../.env',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_path']);

        $this->assertDatabaseMissing('learning_materials', [
            'title' => 'Admin Traversing Material',
        ]);
    }
}
