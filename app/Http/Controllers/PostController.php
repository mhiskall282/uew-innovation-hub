<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\ActivityLog;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    // Public listings
    public function index(Request $request): View
    {
        $posts = Post::published()
            ->when($request->type && $request->type !== 'all', function ($q, $type) {
                $q->where('type', $type);
            })
            ->latest('published_at')
            ->paginate(9);

        return view('posts.index', compact('posts'));
    }

    public function show(Post $post): View
    {
        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('posts.show', compact('post', 'relatedPosts'));
    }

    // Cockpit Management
    public function adminIndex(Request $request): View
    {
        $posts = Post::with('author')
            ->when($request->search, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($request->type && $request->type !== 'all', function ($q, $type) {
                $q->where('type', $type);
            })
            ->latest()
            ->paginate(15);

        return view('admin.posts.index', compact('posts'));
    }

    public function create(): View
    {
        $categories = ['General', 'Grants & Awards', 'Technology & Society', 'Opportunities', 'Research Highlights'];
        return view('admin.posts.create', compact('categories'));
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['author_id'] = $request->user()->id;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['published_at'] = $request->published_at ?? now();

        $post = Post::create($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'post.created',
            'description' => "Published {$post->type}: {$post->title}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.posts.index')
            ->with('status', 'Article published successfully.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $title = $post->title;
        $post->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'post.deleted',
            'description' => "Removed article: {$title}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.posts.index')
            ->with('status', 'Article removed.');
    }
}
