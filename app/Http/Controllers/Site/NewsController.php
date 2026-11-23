<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $type = PostType::tryFrom((string) $request->query('type'));

        return view('site.news.index', [
            'posts' => Post::published()
                ->with('author:id,name')
                ->when($type, fn ($q) => $q->where('type', $type))
                ->latest('published_at')
                ->paginate(9)
                ->withQueryString(),
            'types' => PostType::cases(),
            'activeType' => $type,
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        $post->load('author:id,name');

        return view('site.news.show', [
            'post' => $post,
            'related' => Post::published()
                ->whereKeyNot($post->id)
                ->where('category', $post->category)
                ->latest('published_at')
                ->take(3)
                ->get(),
        ]);
    }
}
