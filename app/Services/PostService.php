<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * News, blog and announcement authoring.
 *
 * Publishing is a separate permission: staff with content.draft can write,
 * but only staff with content.publish can make a post visible publicly. If a
 * drafter submits a publish date it is ignored and the post stays a draft.
 */
final class PostService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $author): Post
    {
        $post = new Post($data);
        $post->author_id = $author->id;
        $post->is_featured = (bool) ($data['is_featured'] ?? false);
        $post->published_at = $this->publishDate($data, $author, null);
        $post->save();

        $this->audit->record('post.created', "Created {$post->type->label()} \"{$post->title}\" ({$post->state()})", $post);

        return $post;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, array $data, User $editor): Post
    {
        $post->fill($data);
        $post->is_featured = (bool) ($data['is_featured'] ?? false);
        $post->published_at = $this->publishDate($data, $editor, $post->published_at);

        $changes = $this->audit->diff($post);
        unset($changes['content']); // article bodies are too large for the log
        $post->save();

        $this->audit->record('post.updated', "Updated \"{$post->title}\" ({$post->state()})", $post, $changes);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
        $this->audit->record('post.deleted', "Deleted \"{$post->title}\"", $post);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function publishDate(array $data, User $user, ?Carbon $current): ?Carbon
    {
        if (! $user->hasPermission(Permission::ContentPublish)) {
            return $current;
        }

        return match ($data['publish_action'] ?? 'draft') {
            'publish' => $current && $current->isPast() ? $current : now(),
            'schedule' => isset($data['published_at']) ? Carbon::parse($data['published_at']) : $current,
            default => null,
        };
    }
}
