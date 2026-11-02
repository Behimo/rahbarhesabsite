<?php

namespace App\Services\Comments\Rules;

use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\RuleResult;

class HoneypotRule implements CommentGuardRule
{
    public function __construct(private CommentSettings $settings) {}

    public function check(CommentDraft $draft): RuleResult
    {
        if (! $this->settings->bool('honeypot_enabled', true)) {
            return RuleResult::pass();
        }

        if (filled($draft->honeypot)) {
            return RuleResult::spam('honeypot', 100);
        }

        return RuleResult::pass();
    }
}
