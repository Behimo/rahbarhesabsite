<?php

namespace App\Providers;

use App\Services\Comments\CommentGuard;
use App\Services\Comments\CommentSettings;
use App\Services\Comments\Rules\CommentGuardRule;
use Illuminate\Support\ServiceProvider;

class CommentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CommentSettings::class);

        $this->app->singleton(CommentGuard::class, function ($app) {
            $rules = [];

            foreach (config('comments.guard_rules', []) as $ruleClass) {
                $rule = $app->make($ruleClass);

                if ($rule instanceof CommentGuardRule) {
                    $rules[] = $rule;
                }
            }

            return new CommentGuard($app->make(CommentSettings::class), $rules);
        });
    }
}
