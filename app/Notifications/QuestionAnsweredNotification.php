<?php

namespace App\Notifications;

use App\Models\CafeQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuestionAnsweredNotification extends Notification
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
        return [
            'type' => 'question_answered',
            'question_id' => $this->question->id,
            'title' => 'پاسخ به سوال شما',
            'message' => 'کارشناسان شرکت به سوال «'.$this->question->title.'» پاسخ دادند.',
            'url' => route('cafe-soal.show', $this->question->slug),
        ];
    }
}
