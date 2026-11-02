<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Services\Comments\CommentSettings;
use App\Support\AccessCatalog;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return AccessCatalog::allows($user, AccessCatalog::permission('comments', AccessCatalog::VIEW));
    }

    public function update(User $user, Comment $comment): bool
    {
        return AccessCatalog::allows($user, AccessCatalog::permission('comments', AccessCatalog::UPDATE));
    }

    public function delete(User $user, Comment $comment): bool
    {
        return AccessCatalog::allows($user, AccessCatalog::permission('comments', AccessCatalog::DELETE));
    }

    public function deleteOwn(User $user, Comment $comment): bool
    {
        if ($comment->user_id !== $user->id) {
            return false;
        }

        return app(CommentSettings::class)->bool('allow_delete_own', false);
    }

    public function updateOwn(User $user, Comment $comment): bool
    {
        if ($comment->user_id !== $user->id) {
            return false;
        }

        $window = app(CommentSettings::class)->int('edit_window_minutes', 0);

        if ($window <= 0) {
            return false;
        }

        return $comment->created_at && $comment->created_at->gt(now()->subMinutes($window));
    }
}
