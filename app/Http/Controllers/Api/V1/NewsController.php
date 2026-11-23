<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PostType;
use App\Http\Resources\Api\V1\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(PostType::class)],
            'per_page' => ['nullable', 'integer', 'between:1,'.self::MAX_PER_PAGE],
        ]);

        $posts = Post::published()
            ->with('author:id,name')
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->latest('published_at')
            ->paginate($this->perPage())
            ->withQueryString();

        return $this->paginated(PostResource::collection($posts));
    }

    public function show(Post $post): JsonResponse
    {
        abort_unless($post->isPublished(), 404);

        return $this->ok(new PostResource($post->load('author:id,name')));
    }
}
