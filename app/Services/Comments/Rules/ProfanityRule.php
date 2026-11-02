<?php

namespace App\Services\Comments\Rules;

use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\RuleResult;
use App\Support\PersianTextNormalizer;

class ProfanityRule implements CommentGuardRule
{
    public function __construct(private CommentSettings $settings) {}

    public function check(CommentDraft $draft): RuleResult
    {
        if (! $this->settings->bool('profanity_filter_enabled', true)) {
            return RuleResult::pass();
        }

        $words = $this->settings->activeList('profanity_words');

        foreach ($words as $word) {
            if (! PersianTextNormalizer::containsWord($draft->body, $word)) {
                continue;
            }

            $action = $this->settings->get('profanity_action', 'reject');

            return match ($action) {
                'spam' => RuleResult::spam('profanity', 90),
                'pending' => RuleResult::pending('profanity', 70),
                default => RuleResult::reject('profanity', $this->settings->get('rejected_message')),
            };
        }

        return RuleResult::pass();
    }
}
