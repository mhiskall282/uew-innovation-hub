<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Enums\PostType;
use App\Http\Resources\Api\V2\NewsResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Published posts only. Drafts and scheduled posts return 404. */
class NewsController extends ApiController
{
    protected function sorts(): array
    {
        return [
            '-published_at' => [['published_at', 'desc'], ['id', 'desc']],
            'published_at' => [['published_at', 'asc'], ['id', 'asc']],
        ] + parent::sorts();
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $this->validateList($request, [
            'type' => ['nullable', Rule::enum(PostType::class)],
            'category' => ['nullable', 'string', Rule::in(config('hub.post_categories'))],
            'featured' => ['nullable', 'boolean'],
        ]);

        $query = Post::published()
            ->with('author:id,name')
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when(isset($filters['featured']), fn ($q) => $q->where('is_featured', $request->boolean('featured')));

        return $this->paginate($request, $query, NewsResource::class);
    }

    public function show(Post $post): JsonResponse
    {
        abort_unless($post->isPublished(), 404);

        return $this->respond(new NewsResource($post->load('author:id,name')));
    }
}
