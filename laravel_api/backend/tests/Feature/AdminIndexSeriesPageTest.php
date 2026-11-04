<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\StudentIndexCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIndexSeriesPageTest extends TestCase
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
            'index_prefix' => 'TIT',
            'index_seq_start' => 1,
            'index_format' => 'grade',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => 1,
            'email' => 'admin@tit.lk',
        ]);

        StudentIndexCounter::create([
            'institute_id' => 1,
            'batch_year' => 8,
            'last_seq' => 5,
        ]);
    }

    /**
     * Test format radios reflect 'grade' format when stored.
     */
    public function test_index_series_format_radio_reflects_grade_format(): void
    {
        $this->institute->update([
            'index_format' => 'grade',
            'index_seq_start' => 1,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.settings.index-series'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Must have format_grade checked
        $this->assertMatchesRegularExpression('/id="format_grade"[^>]*checked/i', $content);
        // Must NOT have format_batch checked
        $this->assertDoesNotMatchRegularExpression('/id="format_batch"[^>]*checked/i', $content);

        // Verify institute name in subtitle
        $response->assertSee('TiT Institute');
        // Verify tokenized classes
        $response->assertSee('index-series-page');
        $response->assertSee('data-label="Series"', false);
        $response->assertSee('data-label="New Series"', false);
    }

    /**
     * Test format radios reflect 'batch' format when stored.
     */
    public function test_index_series_format_radio_reflects_batch_format(): void
    {
        $this->institute->update([
            'index_format' => 'batch',
            'index_seq_start' => 100,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.settings.index-series'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Must have format_batch checked
        $this->assertMatchesRegularExpression('/id="format_batch"[^>]*checked/i', $content);
        // Must NOT have format_grade checked
        $this->assertDoesNotMatchRegularExpression('/id="format_grade"[^>]*checked/i', $content);
    }

    /**
     * Verify single content-body wrapper in the rendered HTML (no double nesting).
     */
    public function test_rendered_index_series_page_has_exactly_one_content_body(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('admin.settings.index-series'));
        $response->assertStatus(200);

        $content = $response->getContent();
        $count = substr_count($content, 'class="content-body"');
        $this->assertEquals(1, $count, 'Rendered page must contain exactly one class="content-body" from layouts.admin');
    }
}
