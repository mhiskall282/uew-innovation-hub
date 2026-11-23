<?php

namespace Tests\Feature\Workspace;

use App\Enums\Role;
use App\Models\Post;
use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function post_(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Open Day at the Hub',
            'type' => 'news',
            'category' => 'Events',
            'summary' => 'Students visited the farm.',
            'content' => "Over 200 visitors.\n\n## Highlights",
            'publish_action' => 'draft',
        ], $overrides);
    }

    public function test_intern_drafts_stay_drafts_even_if_they_ask_to_publish(): void
    {
        $intern = $this->signInAs(Role::Intern);

        $this->post('/workspace/posts', $this->post_(['publish_action' => 'publish', 'published_at' => now()->subDay()->toDateTimeString()]));

        $post = Post::sole();
        $this->assertNull($post->published_at);
        $this->assertSame($intern->id, $post->author_id);
        $this->get("/news/{$post->slug}")->assertNotFound();
    }

    public function test_intern_can_edit_own_draft_but_not_others_or_published_posts(): void
    {
        $intern = $this->signInAs(Role::Intern);
        $own = Post::factory()->draft()->create(['author_id' => $intern->id]);
        $someoneElses = Post::factory()->draft()->create();
        $published = Post::factory()->create(['author_id' => $intern->id]);

        $this->put("/workspace/posts/{$own->slug}", $this->post_(['title' => 'Edited']))->assertRedirect();
        $this->put("/workspace/posts/{$someoneElses->slug}", $this->post_())->assertForbidden();
        $this->put("/workspace/posts/{$published->slug}", $this->post_())->assertForbidden();
        $this->delete("/workspace/posts/{$published->slug}")->assertForbidden();

        $this->assertSame('Edited', $own->fresh()->title);
    }

    public function test_officer_publishes_and_schedules(): void
    {
        $this->signInAs(Role::Officer);

        $this->post('/workspace/posts', $this->post_(['title' => 'Live Now', 'publish_action' => 'publish']));
        $this->post('/workspace/posts', $this->post_(['title' => 'Later', 'publish_action' => 'schedule', 'published_at' => now()->addDays(3)->format('Y-m-d\TH:i')]));

        $live = Post::where('title', 'Live Now')->sole();
        $later = Post::where('title', 'Later')->sole();

        $this->assertSame('published', $live->state());
        $this->assertSame('scheduled', $later->state());
        $this->get("/news/{$live->slug}")->assertOk();
        $this->get("/news/{$later->slug}")->assertNotFound();
    }

    public function test_scheduling_in_the_past_is_rejected(): void
    {
        $this->signInAs(Role::Officer);

        $this->post('/workspace/posts', $this->post_(['publish_action' => 'schedule', 'published_at' => now()->subHour()->toDateTimeString()]))
            ->assertSessionHasErrors('published_at');
    }

    public function test_officer_can_unpublish_by_saving_as_draft(): void
    {
        $this->signInAs(Role::Officer);
        $post = Post::factory()->create();

        $this->put("/workspace/posts/{$post->slug}", $this->post_(['publish_action' => 'draft']));

        $this->assertNull($post->fresh()->published_at);
    }

    public function test_officer_manages_publications_with_pdf_upload(): void
    {
        Storage::fake('private');
        $this->signInAs(Role::Officer);

        $this->post('/workspace/publications', [
            'title' => 'Annual Report 2026',
            'type' => 'Annual Report',
            'authors' => 'UEW Innovation Hub',
            'publish_date' => '2026-06-01',
            'doi_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('doi_url');

        $this->post('/workspace/publications', [
            'title' => 'Annual Report 2026',
            'type' => 'Annual Report',
            'document' => $this->pdf(),
        ])->assertRedirect(route('workspace.publications.index'));

        $publication = Publication::sole();
        $this->assertStringStartsWith('publications/publication_', $publication->pdf_file_path);
        $this->get(route('site.documents.publication', $publication))->assertOk();

        $this->delete("/workspace/publications/{$publication->slug}")->assertRedirect();
        $this->assertSoftDeleted($publication);
    }

    public function test_interns_cannot_manage_publications(): void
    {
        $this->signInAs(Role::Intern);

        $this->post('/workspace/publications', ['title' => 'X', 'type' => 'Newsletter'])->assertForbidden();
    }
}
