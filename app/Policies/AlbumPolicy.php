<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Album;
use App\Models\User;

/**
 * Same split as news posts: anyone who drafts content (interns included) can
 * create albums and upload photos; publishing, editing published albums and
 * deleting need content.publish.
 */
final class AlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ContentDraft);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ContentDraft);
    }

    public function update(User $user, Album $album): bool
    {
        if ($user->hasPermission(Permission::ContentPublish)) {
            return true;
        }

        return $user->hasPermission(Permission::ContentDraft) && ! $album->is_published;
    }

    public function delete(User $user, Album $album): bool
    {
        return $user->hasPermission(Permission::ContentPublish);
    }

    /** Unpublished photos: anyone who works on gallery content. */
    public function viewUnpublished(User $user, Album $album): bool
    {
        return $user->hasPermission(Permission::ContentDraft);
    }
}
