<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentApprovedNotification extends Notification
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
            'type' => 'comment_approved',
            'comment_id' => $this->comment->id,
            'title' => 'تأیید دیدگاه',
            'message' => 'دیدگاه شما تأیید و منتشر شد.',
            'url' => route('panel.comments'),
        ];
    }
}
