<?php

namespace App\Services\Comments\Actions;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentRepliedNotification;
use App\Services\Comments\CommentAvailability;
use App\Services\Comments\CommentGuard;
use App\Services\Comments\CommentSanitizer;
use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\GuardVerdict;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class StoreCommentAction
{
    public function __construct(
        private CommentAvailability $availability,
        private CommentSanitizer $sanitizer,
        private CommentGuard $guard,
        private CommentSettings $settings,
    ) {}

    /**
     * @param  array{body: string, parent_id?: int|null, author_name?: string|null, author_email?: string|null, honeypot?: string|null, ip?: string|null, user_agent?: string|null}  $input
     * @return array{comment: Comment|null, status: string, message: string}
     */
    public function handle(?User $user, Model $commentable, array $input): array
    {
        $parentId = isset($input['parent_id']) ? (int) $input['parent_id'] : null;
        if ($parentId === 0) {
            $parentId = null;
        }

        $this->availability->assertCanComment($user, $commentable, $parentId);

        $body = $this->sanitizer->sanitize($input['body'] ?? '');
        $min = $this->settings->int('min_length', 3);
        $max = $this->settings->int('max_length', 2000);

        if (mb_strlen($body) < $min) {
            throw new RuntimeException("دیدگاه باید حداقل {$min} کاراکتر باشد.");
        }

        if (mb_strlen($body) > $max) {
            throw new RuntimeException("دیدگاه نباید بیشتر از {$max} کاراکتر باشد.");
        }

        $authorName = $user?->name;
        $authorEmail = $user?->email;

        if (! $user) {
            $authorName = $this->sanitizer->sanitize($input['author_name'] ?? '');
            $authorEmail = trim((string) ($input['author_email'] ?? ''));
        }

        $draft = new CommentDraft(
            commentable: $commentable,
            body: $body,
            user: $user,
            authorName: $authorName,
            authorEmail: $authorEmail ?: null,
            ip: $input['ip'] ?? null,
            userAgent: $input['user_agent'] ?? null,
            parentId: $parentId,
            honeypot: $input['honeypot'] ?? null,
        );

        $verdict = $this->guard->inspect($draft);

        if ($verdict->isReject()) {
            throw new RuntimeException($verdict->message ?: (string) $this->settings->get('rejected_message'));
        }

        $status = $this->resolveStatus($user, $commentable, $verdict);

        $comment = new Comment([
            'user_id' => $user?->id,
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'author_ip' => $draft->ip,
            'user_agent' => $draft->userAgent ? mb_substr($draft->userAgent, 0, 500) : null,
            'parent_id' => $parentId,
            'body' => $body,
            'status' => $status,
            'spam_score' => $verdict->score ?: null,
            'spam_reasons' => $verdict->reasons ?: null,
            'approved_at' => $status === Comment::STATUS_APPROVED ? now() : null,
            'is_admin_reply' => false,
        ]);

        $commentable->comments()->save($comment);

        if ($status === Comment::STATUS_APPROVED && $parentId) {
            $this->notifyParentAuthor($comment);
        }

        $message = match ($status) {
            Comment::STATUS_APPROVED => 'دیدگاه شما ثبت شد.',
            Comment::STATUS_SPAM => (string) $this->settings->get('pending_message'),
            default => (string) $this->settings->get('pending_message'),
        };

        return [
            'comment' => $comment,
            'status' => $status,
            'message' => $message,
        ];
    }

    private function resolveStatus(?User $user, Model $commentable, GuardVerdict $verdict): string
    {
        if ($verdict->isSpam()) {
            return Comment::STATUS_SPAM;
        }

        if ($verdict->isPending()) {
            return Comment::STATUS_PENDING;
        }

        return $this->availability->shouldAutoApprove($user, $commentable)
            ? Comment::STATUS_APPROVED
            : Comment::STATUS_PENDING;
    }

    private function notifyParentAuthor(Comment $comment): void
    {
        $parent = $comment->parent()->with('user')->first();

        if (! $parent?->user || $parent->user_id === $comment->user_id) {
            return;
        }

        Notification::send($parent->user, new CommentRepliedNotification($comment));
    }
}
