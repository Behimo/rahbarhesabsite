<?php

namespace App\Services\Comments;

use App\Models\CmsPost;
use App\Models\Comment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

class CommentAvailability
{
    public function __construct(private CommentSettings $settings) {}

    public function typeKey(Model $commentable): ?string
    {
        return $this->settings->typeKeyFor($commentable::class);
    }

    public function isTypeEnabled(Model $commentable): bool
    {
        $key = $this->typeKey($commentable);

        return $key !== null && $this->settings->isTypeEnabled($key);
    }

    public function itemStatus(Model $commentable): string
    {
        $status = (string) ($commentable->comments_status ?? Comment::ITEM_STATUS_INHERIT);

        if (! in_array($status, [Comment::ITEM_STATUS_INHERIT, Comment::ITEM_STATUS_OPEN, Comment::ITEM_STATUS_CLOSED], true)) {
            return Comment::ITEM_STATUS_INHERIT;
        }

        return $status;
    }

    public function isOpen(Model $commentable): bool
    {
        if (! $this->settings->isGloballyEnabled() || ! $this->isTypeEnabled($commentable)) {
            return false;
        }

        $status = $this->itemStatus($commentable);

        if ($status === Comment::ITEM_STATUS_CLOSED) {
            return false;
        }

        if ($status === Comment::ITEM_STATUS_INHERIT && $this->settings->get('default_status', 'open') === 'closed') {
            return false;
        }

        $days = $this->settings->int('auto_close_days', 0);

        if ($days > 0) {
            $anchor = $this->publishedAt($commentable);

            if ($anchor && $anchor->copy()->addDays($days)->lt(now())) {
                return false;
            }
        }

        return true;
    }

    public function moderationMode(Model $commentable): string
    {
        $mode = (string) ($commentable->comments_moderation ?? Comment::MODERATION_INHERIT);

        if ($mode === Comment::MODERATION_MANUAL) {
            return 'manual';
        }

        if ($mode === Comment::MODERATION_AUTO) {
            return 'auto';
        }

        return $this->settings->bool('require_approval', true) ? 'manual' : 'auto';
    }

    public function assertCanComment(?User $user, Model $commentable, ?int $parentId = null): void
    {
        if (! $this->isOpen($commentable)) {
            throw new RuntimeException((string) $this->settings->get('closed_message'));
        }

        $authRequired = $this->settings->bool('auth_required', true);
        $guestsAllowed = $this->settings->bool('guests_allowed', false);

        if ($authRequired && ! $user) {
            throw new RuntimeException('برای ارسال دیدگاه وارد شوید.');
        }

        if (! $authRequired && ! $guestsAllowed && ! $user) {
            throw new RuntimeException('برای ارسال دیدگاه وارد شوید.');
        }

        if ($user) {
            if ($user->isBlocked()) {
                throw new RuntimeException('حساب کاربری شما اجازه ارسال دیدگاه ندارد.');
            }

            if ($user->comments_banned_at) {
                throw new RuntimeException('ارسال دیدگاه برای شما مسدود شده است.');
            }
        }

        if ($parentId) {
            if (! $this->settings->bool('allow_replies', true)) {
                throw new RuntimeException('پاسخ به دیدگاه‌ها غیرفعال است.');
            }

            $parent = Comment::query()
                ->whereKey($parentId)
                ->where('commentable_type', $commentable::class)
                ->where('commentable_id', $commentable->getKey())
                ->approved()
                ->first();

            if (! $parent) {
                throw new RuntimeException('دیدگاه والد یافت نشد.');
            }

            $maxDepth = max(1, $this->settings->int('max_depth', 1));

            if ($parent->parent_id !== null && $maxDepth <= 1) {
                throw new RuntimeException('عمق پاسخ‌ها مجاز نیست.');
            }
        }
    }

    public function shouldAutoApprove(?User $user, Model $commentable): bool
    {
        if ($this->moderationMode($commentable) === 'auto') {
            if ($user && $this->settings->bool('auto_approve_known_users', false)) {
                return true;
            }

            return true;
        }

        if ($user && $this->settings->bool('auto_approve_known_users', false)) {
            return Comment::query()
                ->where('user_id', $user->id)
                ->approved()
                ->exists();
        }

        return false;
    }

    private function publishedAt(Model $commentable): ?Carbon
    {
        if ($commentable instanceof CmsPost && $commentable->published_at) {
            return $commentable->published_at;
        }

        if ($commentable instanceof ShopProduct && $commentable->created_at) {
            return $commentable->created_at;
        }

        return $commentable->getAttribute('created_at');
    }
}
