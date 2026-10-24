<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminIndexPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $admin;
    private User $studentAssigned;
    private User $studentUnassigned;
    private User $regularUser;

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

        $this->studentAssigned = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'Kasun Perera',
            'email' => 'kasun@tit.lk',
            'index_number' => 'TiT/28/100',
            'registration_status' => 'approved',
            'current_grade' => '10',
        ]);

        $this->studentUnassigned = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'Nimal Silva',
            'email' => 'nimal@tit.lk',
            'index_number' => null,
            'registration_status' => 'approved',
            'current_grade' => '10',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'email' => 'user@tit.lk',
        ]);
    }

    /**
     * 1. GET route('admin.students.index') returns 200, contains tools links and counter badge,
     *    and supports filtering by has_index=assigned and has_index=unassigned.
     */
    public function test_admin_students_index_renders_and_filters(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('admin.students.index'));
        $response->assertStatus(200);

        // Verify tool links and visible counter
        $response->assertSee(route('admin.students.assign-index-tool'));
        $response->assertSee(route('admin.students.import-index'));
        $response->assertSee('1 of 2 students have an index number');

        // Test filter ?has_index=assigned
        $responseAssigned = $this->actingAs($this->admin, 'web')->get(route('admin.students.index', ['has_index' => 'assigned']));
        $responseAssigned->assertStatus(200);
        $responseAssigned->assertSee('Kasun Perera');
        $responseAssigned->assertDontSee('Nimal Silva');

        // Test filter ?has_index=unassigned
        $responseUnassigned = $this->actingAs($this->admin, 'web')->get(route('admin.students.index', ['has_index' => 'unassigned']));
        $responseUnassigned->assertStatus(200);
        $responseUnassigned->assertSee('Nimal Silva');
        $responseUnassigned->assertDontSee('Kasun Perera');
    }

    /**
     * 2. GET route('admin.students.assign-index-tool') returns 200 (not 302),
     *    and displays preview/run controls (order radios and batch source options).
     */
    public function test_admin_students_assign_index_tool_renders(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('admin.students.assign-index-tool'));
        $response->assertStatus(200);
        $response->assertSee('orderRegistered');
        $response->assertSee('orderName');
        $response->assertSee('batchSourceMapping');
        $response->assertSee('batchSourceSingle');
    }

    /**
     * 3. GET route('admin.students.import-index') returns 200 (not 302),
     *    and displays the CSV upload form.
     */
    public function test_admin_students_import_index_renders(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('admin.students.import-index'));
        $response->assertStatus(200);
        $response->assertSee('uploadForm');
        $response->assertSee('csvFileInput');
    }

    /**
     * 4. GET route('admin.settings.index-batches') returns 200.
     */
    public function test_admin_settings_index_batches_renders(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('admin.settings.index-batches'));
        $response->assertStatus(200);
        $response->assertSee('Index Number Batches');
        $response->assertSee('Grade &rarr; Batch Year', false);
    }

    /**
     * 5. GET route('admin.students.show') and edit page still work (return 200).
     */
    public function test_admin_students_show_and_edit_routes_render(): void
    {
        $showResponse = $this->actingAs($this->admin, 'web')->get(route('admin.students.show', $this->studentAssigned->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($this->studentAssigned->full_name);

        $editResponse = $this->actingAs($this->admin, 'web')->get(route('admin.students.edit', $this->studentAssigned->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee($this->studentAssigned->full_name);
    }

    /**
     * 6. Generic guard test: all named GET routes starting with admin.students.
     *    that take no parameters must return 200.
     */
    public function test_all_parameterless_admin_student_get_routes_return_200(): void
    {
        $testedCount = 0;
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName() ?? '';
            if (in_array('GET', $route->methods()) && str_starts_with($name, 'admin.students.')) {
                if (empty($route->parameterNames())) {
                    $response = $this->actingAs($this->admin, 'web')->get(route($name));
                    $statusCode = method_exists($response, 'status') ? $response->status() : $response->getStatusCode();
                    $this->assertEquals(
                        200,
                        $statusCode,
                        "Route [{$name}] ({$route->uri()}) returned {$statusCode} instead of 200."
                    );
                    $testedCount++;
                }
            }
        }

        $this->assertGreaterThanOrEqual(4, $testedCount, 'At least 4 parameterless admin.students routes should be tested.');
    }

    /**
     * 7a. Guest redirects to admin login on all protected routes.
     */
    public function test_guest_redirected_to_admin_login(): void
    {
        $protectedRoutes = [
            route('admin.students.index'),
            route('admin.students.assign-index-tool'),
            route('admin.students.import-index'),
            route('admin.settings.index-batches'),
            route('admin.students.show', $this->studentAssigned->id),
            route('admin.students.edit', $this->studentAssigned->id),
        ];

        foreach ($protectedRoutes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('admin.login'));
        }
    }

    /**
     * 7b. Non-admin (regular user) redirects to admin login on all protected routes.
     */
    public function test_non_admin_redirected_to_admin_login(): void
    {
        $protectedRoutes = [
            route('admin.students.index'),
            route('admin.students.assign-index-tool'),
            route('admin.students.import-index'),
            route('admin.settings.index-batches'),
            route('admin.students.show', $this->studentAssigned->id),
            route('admin.students.edit', $this->studentAssigned->id),
        ];

        foreach ($protectedRoutes as $url) {
            $response = $this->actingAs($this->regularUser, 'web')->get($url);
            $response->assertRedirect(route('admin.login'));
        }
    }
}
