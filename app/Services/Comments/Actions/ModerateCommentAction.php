<?php

namespace App\Services\Comments\Actions;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentApprovedNotification;
use App\Notifications\CommentRepliedNotification;
use App\Services\AuditService;
use App\Services\Comments\CommentSanitizer;
use App\Services\Comments\CommentSettings;
use Illuminate\Support\Facades\Notification;

class ModerateCommentAction
{
    public function __construct(
        private AuditService $audit,
        private CommentSettings $settings,
        private CommentSanitizer $sanitizer,
    ) {}

    public function approve(Comment $comment, User $admin): Comment
    {
        $wasApproved = $comment->isApproved();

        $comment->fill([
            'status' => Comment::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ])->save();

        $this->log($admin, $comment, 'approve');

        if (! $wasApproved && $comment->user_id) {
            $comment->loadMissing('user');
            if ($comment->user) {
                Notification::send($comment->user, new CommentApprovedNotification($comment));
            }
        }

        if (! $wasApproved && $comment->parent_id) {
            $this->notifyReply($comment);
        }

        return $comment->fresh();
    }

    public function reject(Comment $comment, User $admin): Comment
    {
        $comment->fill([
            'status' => Comment::STATUS_REJECTED,
            'approved_at' => null,
            'approved_by' => $admin->id,
        ])->save();

        $this->log($admin, $comment, 'reject');

        return $comment->fresh();
    }

    public function markSpam(Comment $comment, User $admin): Comment
    {
        $comment->fill([
            'status' => Comment::STATUS_SPAM,
            'approved_at' => null,
        ])->save();

        $this->log($admin, $comment, 'spam');

        return $comment->fresh();
    }

    public function trash(Comment $comment, User $admin): Comment
    {
        $comment->fill(['status' => Comment::STATUS_TRASH])->save();
        $this->log($admin, $comment, 'trash');

        return $comment->fresh();
    }

    public function restore(Comment $comment, User $admin): Comment
    {
        $comment->fill([
            'status' => Comment::STATUS_PENDING,
            'approved_at' => null,
        ])->save();

        $this->log($admin, $comment, 'restore');

        return $comment->fresh();
    }

    public function destroy(Comment $comment, User $admin): void
    {
        $id = $comment->id;
        $comment->replies()->delete();
        $comment->delete();
        $this->audit->log('comment.destroy', null, ['comment_id' => $id, 'admin_id' => $admin->id]);
    }

    public function togglePin(Comment $comment, User $admin): Comment
    {
        $comment->is_pinned = ! $comment->is_pinned;
        $comment->save();
        $this->log($admin, $comment, $comment->is_pinned ? 'pin' : 'unpin');

        return $comment->fresh();
    }

    public function banUserFromComments(Comment $comment, User $admin): void
    {
        if (! $comment->user_id) {
            return;
        }

        User::query()->whereKey($comment->user_id)->update(['comments_banned_at' => now()]);
        $this->log($admin, $comment, 'ban_user_comments');
    }

    public function blockIp(Comment $comment, User $admin): void
    {
        $ip = $comment->author_ip;

        if (! $ip) {
            return;
        }

        $ips = $this->settings->lines('blocked_ips');
        if (! in_array($ip, $ips, true)) {
            $ips[] = $ip;
            $all = $this->settings->all();
            $all['blocked_ips'] = $ips;
            $this->settings->save($all);
        }

        $this->log($admin, $comment, 'block_ip', ['ip' => $ip]);
    }

    public function replyAsAdmin(Comment $parent, User $admin, string $body): Comment
    {
        $clean = $this->sanitizer->sanitize($body);

        $comment = $parent->commentable->comments()->create([
            'user_id' => $admin->id,
            'author_name' => $admin->name,
            'author_email' => $admin->email,
            'parent_id' => $parent->id,
            'body' => $clean,
            'status' => Comment::STATUS_APPROVED,
            'is_admin_reply' => true,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);

        $this->log($admin, $comment, 'admin_reply');
        $this->notifyReply($comment);

        return $comment;
    }

    private function notifyReply(Comment $comment): void
    {
        $parent = $comment->parent()->with('user')->first();

        if (! $parent?->user || $parent->user_id === $comment->user_id) {
            return;
        }

        Notification::send($parent->user, new CommentRepliedNotification($comment));
    }

    private function log(User $admin, Comment $comment, string $action, array $meta = []): void
    {
        $this->audit->log('comment.'.$action, $comment, array_merge(['admin_id' => $admin->id], $meta));
    }
}
