<?php

namespace Tests\Feature\Site;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'projects' => ['/projects'],
            'publications' => ['/publications'],
            'news' => ['/news'],
            'partners' => ['/partners'],
            'programmes' => ['/programmes'],
            'contact' => ['/contact'],
            'sign up' => ['/sign-up'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_pages_render_with_account_links_but_no_staff_internals(string $url): void
    {
        Project::factory()->count(2)->create();
        Post::factory()->create();
        Publication::factory()->create();
        Organization::factory()->create();
        \App\Models\Program::factory()->create();

        $this->get($url)
            ->assertOk()
            ->assertDontSee('/workspace', false)
            ->assertDontSee('Staff Sign In')
            ->assertDontSee('Cockpit');
    }

    public function test_guests_see_sign_in_and_create_account(): void
    {
        $this->get('/')->assertSee('Sign in')->assertSee('Create account');
    }

    public function test_old_apply_and_track_urls_redirect(): void
    {
        $this->get('/apply')->assertRedirect('/programmes');
        $this->get('/track')->assertRedirect('/portal');
    }

    public function test_cms_pages_render_and_hidden_ones_404(): void
    {
        \App\Models\Page::create(['slug' => 'about', 'title' => 'About the Hub', 'body' => '## Hello', 'is_published' => true]);
        \App\Models\Page::create(['slug' => 'secret', 'title' => 'Secret', 'body' => 'x', 'is_published' => false]);

        $this->get('/about')->assertOk()->assertSee('<h2>Hello</h2>', false);
        $this->get('/secret')->assertNotFound();
    }

    public function test_home_shows_public_impact_figures_and_active_projects(): void
    {
        Project::factory()->create(['title' => 'Robotics Clubs', 'beneficiaries_reached' => 1200, 'status' => ProjectStatus::Active]);
        Project::factory()->internal()->create(['title' => 'Secret Pilot', 'beneficiaries_reached' => 5000]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Robotics Clubs')
            ->assertDontSee('Secret Pilot')
            ->assertSee('1,200');
    }

    public function test_internal_projects_are_hidden_from_listing_and_detail(): void
    {
        $public = Project::factory()->create(['title' => 'Open Project']);
        $internal = Project::factory()->internal()->create(['title' => 'Internal Project']);

        $this->get('/projects')->assertSee('Open Project')->assertDontSee('Internal Project');
        $this->get("/projects/{$public->slug}")->assertOk()->assertSee('Open Project');
        $this->get("/projects/{$internal->slug}")->assertNotFound();
    }

    public function test_projects_can_be_filtered_by_focus_area_and_search(): void
    {
        Project::factory()->create(['title' => 'Hydroponics Farm', 'category' => 'Agriculture']);
        Project::factory()->create(['title' => 'Coding Clubs', 'category' => 'STEM']);

        $this->get('/projects?category=Agriculture')->assertSee('Hydroponics Farm')->assertDontSee('Coding Clubs');
        $this->get('/projects?search=coding')->assertSee('Coding Clubs')->assertDontSee('Hydroponics Farm');
    }

    public function test_only_published_posts_are_public(): void
    {
        $published = Post::factory()->create(['title' => 'Live Story', 'content' => "Intro\n\n## Section\n\n<script>alert(1)</script>"]);
        $draft = Post::factory()->draft()->create(['title' => 'Draft Story']);
        $scheduled = Post::factory()->scheduled()->create(['title' => 'Future Story']);

        $this->get('/news')->assertSee('Live Story')->assertDontSee('Draft Story')->assertDontSee('Future Story');

        $this->get("/news/{$published->slug}")
            ->assertOk()
            ->assertSee('<h2>Section</h2>', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->get("/news/{$draft->slug}")->assertNotFound();
        $this->get("/news/{$scheduled->slug}")->assertNotFound();
    }

    public function test_publications_filter_by_type(): void
    {
        Publication::factory()->create(['title' => 'Quarterly Newsletter', 'type' => 'Newsletter']);
        Publication::factory()->create(['title' => 'Policy on Clusters', 'type' => 'Policy Brief']);

        $this->get('/publications?type=Newsletter')->assertSee('Quarterly Newsletter')->assertDontSee('Policy on Clusters');
    }

    public function test_partner_contact_emails_are_not_published(): void
    {
        Organization::factory()->create(['name' => 'Example Foundation', 'contact_email' => 'private@example.org']);

        $this->get('/partners')->assertSee('Example Foundation')->assertDontSee('private@example.org');
    }

    public function test_old_news_url_redirects_permanently(): void
    {
        $this->get('/news-and-blogs')->assertStatus(301)->assertRedirect('/news');
    }

    public function test_unknown_pages_use_the_branded_404(): void
    {
        $this->get('/does-not-exist')->assertNotFound()->assertSee('Page not found');
    }
}
