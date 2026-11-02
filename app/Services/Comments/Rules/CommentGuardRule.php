<?php

namespace App\Services\Comments\Rules;

use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\RuleResult;

interface CommentGuardRule
{
    public function check(CommentDraft $draft): RuleResult;
}
