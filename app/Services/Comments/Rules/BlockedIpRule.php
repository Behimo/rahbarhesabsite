<?php

namespace App\Services\Comments\Rules;

use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\RuleResult;

class BlockedIpRule implements CommentGuardRule
{
    public function __construct(private CommentSettings $settings) {}

    public function check(CommentDraft $draft): RuleResult
    {
        $ip = $draft->ip;

        if ($ip === null || $ip === '') {
            return RuleResult::pass();
        }

        $blocked = $this->settings->lines('blocked_ips');

        if (in_array($ip, $blocked, true)) {
            return RuleResult::reject('blocked_ip', $this->settings->get('rejected_message'));
        }

        return RuleResult::pass();
    }
}
