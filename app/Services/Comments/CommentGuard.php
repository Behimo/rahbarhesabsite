<?php

namespace App\Services\Comments;

use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\GuardVerdict;
use App\Services\Comments\Rules\CommentGuardRule;

class CommentGuard
{
    /**
     * @param  list<CommentGuardRule>  $rules
     */
    public function __construct(
        private CommentSettings $settings,
        private array $rules,
    ) {}

    public function inspect(CommentDraft $draft): GuardVerdict
    {
        $score = 0;
        $reasons = [];
        $forcedAction = null;
        $message = null;

        foreach ($this->rules as $rule) {
            $result = $rule->check($draft);
            $score += $result->score;
            $reasons = array_values(array_unique(array_merge($reasons, $result->reasons)));

            if ($result->action !== null) {
                $forcedAction = $result->action;
                $message = $result->message ?? $message;
            }

            if ($result->stop) {
                return new GuardVerdict(
                    action: $forcedAction ?? GuardVerdict::ACTION_REJECT,
                    score: min(100, $score),
                    reasons: $reasons,
                    message: $message,
                );
            }
        }

        $threshold = $this->settings->int('soft_score_threshold', 30);

        if ($forcedAction === GuardVerdict::ACTION_PENDING) {
            return new GuardVerdict(GuardVerdict::ACTION_PENDING, min(100, $score), $reasons, $message);
        }

        if ($score >= $threshold) {
            return new GuardVerdict(GuardVerdict::ACTION_PENDING, min(100, $score), $reasons, $message);
        }

        return new GuardVerdict(GuardVerdict::ACTION_ALLOW, min(100, $score), $reasons, $message);
    }
}
