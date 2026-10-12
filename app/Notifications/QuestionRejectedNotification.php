<?php

namespace App\Notifications;

use App\Models\CafeQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuestionRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public CafeQuestion $question)
    {
    }

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
        $reason = $this->question->rejection_reason ?: 'بدون توضیح';

        return [
            'type' => 'question_rejected',
            'question_id' => $this->question->id,
            'title' => 'رد شدن سوال شما',
            'message' => 'سوال «'.$this->question->title.'» رد شد. علت: '.$reason,
            'url' => route('panel.questions'),
        ];
    }
}
