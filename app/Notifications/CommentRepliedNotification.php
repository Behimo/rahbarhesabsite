<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentRepliedNotification extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment_replied',
            'comment_id' => $this->comment->id,
            'parent_id' => $this->comment->parent_id,
            'title' => 'پاسخ به دیدگاه شما',
            'message' => 'به دیدگاه شما پاسخی داده شد.',
            'url' => route('panel.comments'),
        ];
    }
}
