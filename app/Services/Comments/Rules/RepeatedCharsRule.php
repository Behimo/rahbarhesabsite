<?php

namespace App\Services\Comments\Rules;

use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\RuleResult;

class RepeatedCharsRule implements CommentGuardRule
{
    public function __construct(private CommentSettings $settings) {}

    public function check(CommentDraft $draft): RuleResult
    {
        $max = $this->settings->int('max_repeat_chars', 5);

        if ($max <= 0) {
            return RuleResult::pass();
        }

        if (preg_match('/(.)\1{'.$max.',}/u', $draft->body)) {
            return RuleResult::pass(25, ['repeated_chars']);
        }

        $lettersOnly = preg_replace('/\s+/u', '', $draft->body) ?? '';
        if ($lettersOnly !== '' && preg_match('/^[^\p{L}\p{N}]+$/u', $lettersOnly)) {
            return RuleResult::pass(30, ['symbols_only']);
        }

        return RuleResult::pass();
    }
}
