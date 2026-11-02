<?php

namespace App\Services\Comments\Rules;

use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\RuleResult;

class LinkLimitRule implements CommentGuardRule
{
    public function __construct(private CommentSettings $settings) {}

    public function check(CommentDraft $draft): RuleResult
    {
        $max = $this->settings->int('max_links', 1);
        $count = preg_match_all('/https?:\/\/|www\./iu', $draft->body) ?: 0;

        if ($count <= $max) {
            if ($count > 0 && $this->settings->bool('links_force_moderation', true)) {
                return RuleResult::pending('contains_links', 35);
            }

            return RuleResult::pass($count > 0 ? 10 : 0, $count > 0 ? ['contains_links'] : []);
        }

        $action = $this->settings->get('block_on_max_links', 'spam');

        return match ($action) {
            'reject' => RuleResult::reject('too_many_links', $this->settings->get('rejected_message')),
            'pending' => RuleResult::pending('too_many_links', 60),
            default => RuleResult::spam('too_many_links', 70),
        };
    }
}
