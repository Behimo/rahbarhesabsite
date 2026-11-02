<?php

namespace Tests\Unit;

use App\Models\CmsPost;
use App\Services\Comments\CommentGuard;
use App\Services\Comments\CommentSettings;
use App\Services\Comments\DTO\CommentDraft;
use App\Services\Comments\DTO\GuardVerdict;
use App\Services\Comments\Rules\BlacklistRule;
use App\Services\Comments\Rules\BlockedIpRule;
use App\Services\Comments\Rules\HoneypotRule;
use App\Services\Comments\Rules\LinkLimitRule;
use App\Services\Comments\Rules\ProfanityRule;
use App\Services\Comments\Rules\RepeatedCharsRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentGuardTest extends TestCase
{
    use RefreshDatabase;

    private function settings(array $overrides = []): CommentSettings
    {
        $settings = app(CommentSettings::class);
        $settings->save(array_replace_recursive(config('comments.defaults'), $overrides));

        return $settings;
    }

    private function draft(string $body, array $extra = []): CommentDraft
    {
        $post = new CmsPost(['id' => 1]);

        return new CommentDraft(
            commentable: $post,
            body: $body,
            user: null,
            authorName: 'Guest',
            authorEmail: 'a@b.com',
            ip: $extra['ip'] ?? '1.1.1.1',
            userAgent: 'test',
            parentId: null,
            honeypot: $extra['honeypot'] ?? null,
        );
    }

    public function test_honeypot_marks_spam(): void
    {
        $settings = $this->settings();
        $guard = new CommentGuard($settings, [new HoneypotRule($settings)]);
        $verdict = $guard->inspect($this->draft('سلام', ['honeypot' => 'bot']));

        $this->assertTrue($verdict->isSpam());
        $this->assertContains('honeypot', $verdict->reasons);
    }

    public function test_blocked_ip_rejects(): void
    {
        $settings = $this->settings(['blocked_ips' => ['9.9.9.9']]);
        $guard = new CommentGuard($settings, [new BlockedIpRule($settings)]);
        $verdict = $guard->inspect($this->draft('سلام', ['ip' => '9.9.9.9']));

        $this->assertTrue($verdict->isReject());
    }

    public function test_profanity_rejects(): void
    {
        $settings = $this->settings([
            'profanity_filter_enabled' => true,
            'profanity_words' => ['بدجنس'],
            'profanity_action' => 'reject',
        ]);
        $guard = new CommentGuard($settings, [new ProfanityRule($settings)]);
        $verdict = $guard->inspect($this->draft('او بدجنس است'));

        $this->assertSame(GuardVerdict::ACTION_REJECT, $verdict->action);
    }

    public function test_blacklist_marks_spam(): void
    {
        $settings = $this->settings([
            'blacklist_words' => ['ویاگرا'],
            'blacklist_action' => 'spam',
        ]);
        $guard = new CommentGuard($settings, [new BlacklistRule($settings)]);
        $verdict = $guard->inspect($this->draft('خرید ویاگرا'));

        $this->assertTrue($verdict->isSpam());
    }

    public function test_too_many_links_marks_spam(): void
    {
        $settings = $this->settings([
            'max_links' => 1,
            'block_on_max_links' => 'spam',
            'links_force_moderation' => false,
        ]);
        $guard = new CommentGuard($settings, [new LinkLimitRule($settings), new RepeatedCharsRule($settings)]);
        $verdict = $guard->inspect($this->draft('ببین https://a.com و https://b.com'));

        $this->assertTrue($verdict->isSpam());
    }
}
