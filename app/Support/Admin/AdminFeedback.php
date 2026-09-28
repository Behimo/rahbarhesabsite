<?php

namespace App\Support\Admin;

use Illuminate\Support\ViewErrorBag;

class AdminFeedback
{
    /**
     * @var array<string, string>
     */
    private const SESSION_LEVELS = [
        'success' => 'success',
        'status' => 'success',
        'error' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
    ];

    /**
     * @return list<FeedbackNotice>
     */
    public function notices(): array
    {
        $notices = [];

        foreach (self::SESSION_LEVELS as $key => $level) {
            $value = session($key);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $notices[] = new FeedbackNotice($level, trim($value));
        }

        $errors = view()->shared('errors');

        if ($errors instanceof ViewErrorBag) {
            foreach ($errors->all() as $message) {
                if (! is_string($message) || trim($message) === '') {
                    continue;
                }

                $notices[] = new FeedbackNotice('danger', trim($message));
            }
        }

        return $this->unique($notices);
    }

    /**
     * @param  list<FeedbackNotice>  $notices
     * @return list<FeedbackNotice>
     */
    private function unique(array $notices): array
    {
        $seen = [];
        $unique = [];

        foreach ($notices as $notice) {
            $key = $notice->level.'|'.$notice->message;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $notice;
        }

        return $unique;
    }
}
