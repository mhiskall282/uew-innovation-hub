<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\Post;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_endpoints(): void
    {
        $this->getJson('/api/v1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['version', 'endpoints' => ['stats', 'projects', 'publications', 'news', 'partners']]]);
    }

    public function test_stats_only_count_public_projects(): void
    {
        Project::factory()->create(['beneficiaries_reached' => 100, 'grant_amount' => 1000]);
        Project::factory()->internal()->create(['beneficiaries_reached' => 900, 'grant_amount' => 9000]);

        $this->getJson('/api/v1/stats')
            ->assertOk()
            ->assertJsonPath('data.projects', 1)
            ->assertJsonPath('data.beneficiaries_reached', 100)
            ->assertJsonPath('data.currency', 'GHS');
    }

    public function test_projects_are_paginated_filtered_and_hide_internal_fields(): void
    {
        Project::factory()->count(3)->create(['category' => 'STEM']);
        Project::factory()->create(['category' => 'Agriculture']);
        $internal = Project::factory()->internal()->create(['category' => 'STEM']);

        $response = $this->getJson('/api/v1/projects?focus_area=STEM&per_page=2');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['slug', 'title', 'focus_area', 'status', 'beneficiaries' => ['target', 'reached', 'progress_percent'], 'url']], 'links' => ['next']]);

        $this->assertStringNotContainsString($internal->slug, $response->getContent());
        $this->assertArrayNotHasKey('created_by', $response->json('data.0'));
        $this->assertArrayNotHasKey('is_public', $response->json('data.0'));
        $this->assertArrayNotHasKey('report_file_path', $response->json('data.0'));

        $this->getJson("/api/v1/projects/{$internal->slug}")->assertNotFound()->assertExactJson(['success' => false, 'message' => 'Resource not found.']);
    }

    public function test_invalid_filters_return_a_422_envelope(): void
    {
        $this->getJson('/api/v1/projects?focus_area=Astrology&per_page=500')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['focus_area', 'per_page']);
    }

    public function test_publications_and_news_endpoints(): void
    {
        Publication::factory()->create(['title' => 'Policy Brief One', 'type' => 'Policy Brief']);
        $live = Post::factory()->create(['title' => 'Live Post']);
        $draft = Post::factory()->draft()->create();

        $this->getJson('/api/v1/publications?type=Policy%20Brief')->assertOk()->assertJsonPath('data.0.title', 'Policy Brief One');

        $this->getJson('/api/v1/news')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.content');
        $this->getJson("/api/v1/news/{$live->slug}")->assertOk()->assertJsonPath('data.title', 'Live Post')->assertJsonStructure(['data' => ['content']]);
        $this->getJson("/api/v1/news/{$draft->slug}")->assertNotFound();
    }

    public function test_partners_endpoint_never_exposes_contact_emails(): void
    {
        Organization::factory()->create(['contact_email' => 'secret@example.org']);

        $response = $this->getJson('/api/v1/partners')->assertOk();

        $this->assertStringNotContainsString('secret@example.org', $response->getContent());
    }

    public function test_responses_are_cacheable_and_rate_limited(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=60', $response->headers->get('Cache-Control'));
        $response->assertHeader('X-RateLimit-Limit', '60');
    }
}
