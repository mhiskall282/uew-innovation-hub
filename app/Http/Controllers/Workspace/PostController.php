<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\PostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Post::class);

        $state = in_array($request->query('state'), ['draft', 'scheduled', 'published'], true) ? $request->query('state') : null;
        $type = PostType::tryFrom((string) $request->query('type'));

        return view('workspace.posts.index', [
            'posts' => Post::query()
                ->with('author:id,name')
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when($state === 'draft', fn ($q) => $q->whereNull('published_at'))
                ->when($state === 'scheduled', fn ($q) => $q->where('published_at', '>', now()))
                ->when($state === 'published', fn ($q) => $q->published())
                ->search($request->query('search'), ['title', 'summary'])
                ->latest('updated_at')
                ->paginate(15)
                ->withQueryString(),
            'types' => PostType::cases(),
            'filters' => ['state' => $state, 'type' => $type?->value, 'search' => $request->query('search')],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Post::class);

        return view('workspace.posts.create', $this->formData(new Post(['type' => PostType::News, 'category' => 'General'])));
    }

    public function store(PostRequest $request): RedirectResponse
    {
        Gate::authorize('create', Post::class);

        $post = $this->posts->create($request->validated(), $request->user());

        return redirect()->route('workspace.posts.edit', $post)->with('status', $this->message($post, 'saved'));
    }

    public function edit(Post $post): View
    {
        Gate::authorize('update', $post);

        return view('workspace.posts.edit', $this->formData($post));
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $this->posts->update($post, $request->validated(), $request->user());

        return redirect()->route('workspace.posts.edit', $post)->with('status', $this->message($post, 'updated'));
    }

    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $this->posts->delete($post);

        return redirect()->route('workspace.posts.index')->with('status', "\"{$post->title}\" was deleted.");
    }

    /** @return array<string, mixed> */
    private function formData(Post $post): array
    {
        return [
            'post' => $post,
            'types' => PostType::options(),
            'categories' => config('hub.post_categories'),
        ];
    }

    private function message(Post $post, string $verb): string
    {
        return match ($post->state()) {
            'published' => "Post {$verb} and published.",
            'scheduled' => "Post {$verb} and scheduled for {$post->published_at->format('j M Y, H:i')}.",
            default => "Draft {$verb}.",
        };
    }
}
