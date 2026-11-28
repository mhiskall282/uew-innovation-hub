<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ApplicationStatus;
use App\Models\Album;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\Project;
use App\Models\Publication;
use App\Models\User;
use App\Services\GalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_version_index_lists_both_versions_and_marks_v2_current(): void
    {
        $this->getJson('/api')
            ->assertOk()
            ->assertJsonPath('data.current', 'v2')
            ->assertJsonPath('data.versions.0.version', 'v1')
            ->assertJsonPath('data.versions.0.status', 'supported')
            ->assertJsonPath('data.versions.1.version', 'v2')
            ->assertJsonPath('data.versions.1.status', 'current');

        $this->getJson('/api/v2')
            ->assertOk()
            ->assertHeader('API-Version', 'v2')
            ->assertJsonPath('data.version', 'v2')
            ->assertJsonStructure(['data' => ['endpoints' => ['projects', 'albums', 'reference'], 'conventions']]);

        $this->getJson('/api/v1')->assertOk()->assertHeader('API-Version', 'v1')->assertJsonPath('success', true);
    }

    public function test_deprecated_versions_send_deprecation_and_sunset_headers(): void
    {
        config(['api.versions.v1.deprecated_at' => '2027-01-01', 'api.versions.v1.sunset_at' => '2027-07-01']);

        $response = $this->getJson('/api/v1/stats')->assertOk();
        $response->assertHeader('Deprecation', '@'.strtotime('2027-01-01 00:00:00 UTC'));
        $response->assertHeader('Sunset', 'Thu, 01 Jul 2027 00:00:00 GMT');
        $this->assertStringContainsString('rel="deprecation"', (string) $response->headers->get('Link'));

        $this->getJson('/api')->assertJsonPath('data.versions.0.status', 'deprecated')
            ->assertJsonPath('data.versions.0.sunset_at', '2027-07-01');

        $this->getJson('/api/v2/stats')->assertHeaderMissing('Deprecation')->assertHeaderMissing('Sunset');
    }

    public function test_projects_use_cursor_pagination_without_gaps_or_duplicates(): void
    {
        Project::factory()->count(5)->create();
        $internal = Project::factory()->internal()->create();

        $first = $this->getJson('/api/v2/projects?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonMissingPath('meta.total')
            ->assertJsonMissingPath('success');

        $seen = collect($first->json('data'))->pluck('id');
        $cursor = $first->json('meta.next_cursor');
        while ($cursor) {
            $page = $this->getJson('/api/v2/projects?limit=2&cursor='.urlencode($cursor))->assertOk();
            $seen = $seen->merge(collect($page->json('data'))->pluck('id'));
            $cursor = $page->json('meta.next_cursor');
        }

        $this->assertCount(5, $seen);
        $this->assertCount(5, $seen->unique());
        $this->assertNotContains($internal->slug, $seen);
        $this->getJson("/api/v2/projects/{$internal->slug}")->assertNotFound();
    }

    public function test_project_shape_money_includes_and_hidden_fields(): void
    {
        $project = Project::factory()->create(['grant_amount' => 1500]);
        $partner = Organization::factory()->create(['contact_email' => 'private@example.org']);
        $project->organizations()->attach($partner, ['role' => 'Funding Partner']);

        $list = $this->getJson('/api/v2/projects')->assertOk()
            ->assertJsonPath('data.0.id', $project->slug)
            ->assertJsonPath('data.0.grant', ['amount' => '1500.00', 'currency' => 'GHS'])
            ->assertJsonMissingPath('data.0.partners')
            ->assertJsonStructure(['data' => [['id', 'title', 'status', 'beneficiaries', 'updated_at', 'links' => ['self', 'html']]], 'meta', 'links']);
        $this->assertArrayNotHasKey('created_by', $list->json('data.0'));
        $this->assertArrayNotHasKey('is_public', $list->json('data.0'));

        $this->getJson('/api/v2/projects?include=partners')->assertOk()
            ->assertJsonPath('data.0.partners.0.name', $partner->name)
            ->assertJsonPath('data.0.partners.0.id', (string) $partner->id);

        $detail = $this->getJson("/api/v2/projects/{$project->slug}")->assertOk()
            ->assertJsonPath('data.partners.0.role', 'Funding Partner');
        $this->assertStringNotContainsString('private@example.org', $detail->getContent());
    }

    public function test_updated_since_returns_only_changed_records(): void
    {
        $old = Project::factory()->create();
        $new = Project::factory()->create();
        DB::table('projects')->where('id', $old->id)->update(['updated_at' => now()->subDays(10)]);

        $this->getJson('/api/v2/projects?updated_since='.urlencode(now()->subDay()->toIso8601String()))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $new->slug);
    }

    public function test_errors_use_the_v2_envelope_with_machine_readable_codes(): void
    {
        $this->getJson('/api/v2/projects?limit=500&sort=title&include=secrets')
            ->assertStatus(422)
            ->assertJsonPath('error.status', 422)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['message', 'details' => ['limit', 'sort']]]);

        $this->getJson('/api/v2/projects?include=secrets')
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['include']]]);

        $this->getJson('/api/v2/news/does-not-exist')
            ->assertNotFound()
            ->assertExactJson(['error' => ['status' => 404, 'code' => 'not_found', 'message' => 'Resource not found.']]);

        $this->getJson('/api/v2/nothing-here')->assertNotFound()->assertJsonPath('error.code', 'not_found');

        // v1 keeps its original error envelope.
        $this->getJson('/api/v1/news/does-not-exist')->assertNotFound()
            ->assertExactJson(['success' => false, 'message' => 'Resource not found.']);
    }

    public function test_news_sorting_filters_and_content_only_on_detail(): void
    {
        $older = Post::factory()->create(['published_at' => now()->subDays(3), 'category' => 'Events']);
        $newer = Post::factory()->create(['published_at' => now()->subDay(), 'category' => 'General']);
        $draft = Post::factory()->draft()->create();

        $this->getJson('/api/v2/news')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->slug)
            ->assertJsonMissingPath('data.0.content');

        $this->getJson('/api/v2/news?sort=published_at')->assertJsonPath('data.0.id', $older->slug);
        $this->getJson('/api/v2/news?category=Events')->assertJsonCount(1, 'data');

        $this->getJson("/api/v2/news/{$newer->slug}")->assertOk()
            ->assertJsonPath('data.content_format', 'markdown')
            ->assertJsonStructure(['data' => ['content']]);
        $this->getJson("/api/v2/news/{$draft->slug}")->assertNotFound();
    }

    public function test_programmes_report_capacity_without_a_query_per_row(): void
    {
        $full = Program::factory()->published()->create(['capacity' => 1]);
        $open = Program::factory()->published()->create(['capacity' => 5]);
        Program::factory()->draft()->create();
        ProgramApplication::factory()->between($full, User::factory()->member()->create())
            ->status(ApplicationStatus::Submitted)->create();

        DB::enableQueryLog();
        $response = $this->getJson('/api/v2/programmes?availability=open')->assertOk()->assertJsonCount(2, 'data');
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $rows = collect($response->json('data'))->keyBy('id');
        $this->assertFalse($rows[$full->slug]['is_accepting_applications']);
        $this->assertTrue($rows[$open->slug]['is_accepting_applications']);
        $this->assertSame('open', $rows[$open->slug]['availability']);
        $this->assertLessThanOrEqual(4, $queries, 'programme list must not run one query per programme');
    }

    public function test_albums_list_published_events_with_available_photos_only(): void
    {
        Storage::fake('gallery');
        $album = new Album(['title' => 'Pitch Day', 'event_date' => '2026-11-20']);
        $album->forceFill(['is_published' => true, 'published_at' => now()])->save();
        $draft = new Album(['title' => 'Draft Album']);
        $draft->forceFill(['is_published' => false])->save();

        $gallery = app(GalleryService::class);
        $kept = $gallery->addFromPath($album, $this->jpeg(), caption: 'Winners');
        $lost = $gallery->addFromPath($album, $this->jpeg(), caption: 'Lost');
        Storage::disk('gallery')->delete($lost->fresh()->path_large);

        $this->getJson('/api/v2/albums')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $album->slug)
            ->assertJsonPath('data.0.photo_count', 2)
            ->assertJsonMissingPath('data.0.photos');

        $this->getJson("/api/v2/albums/{$album->slug}")->assertOk()
            ->assertJsonCount(1, 'data.photos')
            ->assertJsonPath('data.photos.0.id', (string) $kept->id)
            ->assertJsonPath('data.photos.0.caption', 'Winners')
            ->assertJsonStructure(['data' => ['photos' => [['large' => ['url', 'width', 'height'], 'thumb' => ['url']]]]]);

        $this->getJson("/api/v2/albums/{$draft->slug}")->assertNotFound();
    }

    public function test_reference_stats_partners_publications_and_community(): void
    {
        Organization::factory()->create(['name' => 'Ghana Education Trust', 'contact_email' => 'hidden@example.org']);
        Publication::factory()->create(['type' => 'Policy Brief']);
        Project::factory()->create(['grant_amount' => 250]);

        $this->getJson('/api/v2/reference')->assertOk()
            ->assertJsonPath('data.currency', 'GHS')
            ->assertJsonStructure(['data' => ['focus_areas', 'project_statuses' => [['value', 'label']], 'programme_types', 'post_types']]);

        $this->getJson('/api/v2/stats')->assertOk()
            ->assertJsonPath('data.projects', 1)
            ->assertJsonPath('data.funding_secured.amount', '250.00')
            ->assertJsonStructure(['meta' => ['generated_at']]);

        $partners = $this->getJson('/api/v2/partners?search=education')->assertOk()->assertJsonCount(1, 'data');
        $this->assertStringNotContainsString('hidden@example.org', $partners->getContent());

        $this->getJson('/api/v2/publications?type=Policy%20Brief')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v2/community')->assertOk()->assertJsonPath('data', []);
    }

    public function test_responses_support_etags_caching_and_shared_rate_limits(): void
    {
        Project::factory()->create();

        foreach (['/api/v1/projects', '/api/v2/projects'] as $url) {
            $response = $this->getJson($url)->assertOk()->assertHeader('X-RateLimit-Limit', '60');
            $etag = $response->headers->get('ETag');
            $this->assertNotEmpty($etag);
            $this->assertStringContainsString('max-age=60', (string) $response->headers->get('Cache-Control'));

            $this->getJson($url, ['If-None-Match' => $etag])->assertStatus(304);
        }
    }

    /** Writes a small JPEG to a temp file and returns its path. */
    private function jpeg(): string
    {
        $img = imagecreatetruecolor(800, 600);
        imagefill($img, 0, 0, (int) imagecolorallocate($img, 37, 38, 122));
        $path = tempnam(sys_get_temp_dir(), 'apiv2').'.jpg';
        imagejpeg($img, $path, 85);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return $path;
    }
}
