<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    public static function bootHasComments(): void
    {
        static::forceDeleted(function ($model): void {
            $model->comments()->each(function (Comment $comment): void {
                $comment->replies()->delete();
                $comment->delete();
            });
        });
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function approvedComments(): MorphMany
    {
        return $this->comments()->approved();
    }

    public function rootApprovedComments(): MorphMany
    {
        return $this->approvedComments()->roots();
    }
}
