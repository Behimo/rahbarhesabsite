<?php

namespace App\Services\Comments\Rules;

use App\Models\Comment;
use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\RuleResult;
use Illuminate\Support\Carbon;

class DuplicateCommentRule implements CommentGuardRule
{
    public function __construct(private CommentSettings $settings) {}

    public function check(CommentDraft $draft): RuleResult
    {
        $window = $this->settings->int('duplicate_window_minutes', 30);

        if ($window <= 0 || $draft->body === '') {
            return RuleResult::pass();
        }

        $query = Comment::query()
            ->where('body', $draft->body)
            ->where('created_at', '>=', Carbon::now()->subMinutes($window));

        if ($draft->user) {
            $query->where('user_id', $draft->user->id);
        } elseif ($draft->ip) {
            $query->where('author_ip', $draft->ip);
        } else {
            return RuleResult::pass();
        }

        if ($query->exists()) {
            return RuleResult::reject('duplicate', 'این دیدگاه اخیراً ارسال شده است.');
        }

        return RuleResult::pass();
    }
}
