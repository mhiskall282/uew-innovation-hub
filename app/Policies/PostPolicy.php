<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Post;
use App\Models\User;

/**
 * Publishers (content.publish) can edit and delete any post.
 * Drafters (content.draft) can only edit or delete their own unpublished drafts.
 */
final class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ContentDraft);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ContentDraft);
    }

    public function update(User $user, Post $post): bool
    {
        if ($user->hasPermission(Permission::ContentPublish)) {
            return true;
        }

        return $user->hasPermission(Permission::ContentDraft)
            && $post->author_id === $user->id
            && $post->published_at === null;
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    public function publish(User $user): bool
    {
        return $user->hasPermission(Permission::ContentPublish);
    }
}
