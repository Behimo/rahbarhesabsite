<?php

namespace App\Services\Comments\Rules;

use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\RuleResult;
use App\Support\PersianTextNormalizer;

class BlacklistRule implements CommentGuardRule
{
    public function __construct(private CommentSettings $settings) {}

    public function check(CommentDraft $draft): RuleResult
    {
        $words = $this->settings->activeList('blacklist_words');

        foreach ($words as $word) {
            if (! PersianTextNormalizer::containsWord($draft->body, $word)) {
                continue;
            }

            $action = $this->settings->get('blacklist_action', 'spam');

            return match ($action) {
                'reject' => RuleResult::reject('blacklist', $this->settings->get('rejected_message')),
                'pending' => RuleResult::pending('blacklist', 55),
                default => RuleResult::spam('blacklist', 75),
            };
        }

        return RuleResult::pass();
    }
}
