<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PostManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_published_news_and_blogs(): void
    {
        $post = Post::create([
            'title' => 'Launch of Winneba Innovation MakerSpace',
            'type' => 'news',
            'category' => 'Grants & Awards',
            'summary' => 'A new MakerSpace has officially opened at the South Campus.',
            'content' => 'Full article content detailing the equipment, 3D printers, and microcontrollers available to students.',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/news-and-blogs');
        $response->assertStatus(200);
        $response->assertSee('Launch of Winneba Innovation MakerSpace');

        $showResponse = $this->get("/news-and-blogs/{$post->slug}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Launch of Winneba Innovation MakerSpace');
    }

    public function test_intern_can_create_cms_article(): void
    {
        $intern = User::create([
            'name' => 'Kofi Boateng (NSS)',
            'email' => 'intern@uew.edu.gh',
            'role' => 'intern',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($intern)->post('/admin/posts', [
            'title' => 'Student Robotics Team Competes in Accra',
            'type' => 'news',
            'category' => 'General',
            'summary' => 'UEW student innovators showcase solar vehicles.',
            'content' => 'Comprehensive reporting on the team performance at the national STEM fair.',
            'is_featured' => 1,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect('/admin/posts');
        $this->assertDatabaseHas('posts', [
            'title' => 'Student Robotics Team Competes in Accra',
            'author_id' => $intern->id,
        ]);
    }
}
