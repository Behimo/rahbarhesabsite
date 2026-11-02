<?php

namespace Tests\Feature;

use App\Models\CmsPost;
use App\Models\Comment;
use App\Models\User;
use App\Services\Comments\CommentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    private function configure(array $overrides = []): void
    {
        app(CommentSettings::class)->save(array_replace_recursive(config('comments.defaults'), $overrides));
    }

    private function publishedPost(): CmsPost
    {
        return CmsPost::factory()->create([
            'is_published' => true,
            'status' => CmsPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'comments_status' => 'open',
            'comments_moderation' => 'inherit',
        ]);
    }

    public function test_authenticated_user_can_submit_pending_comment(): void
    {
        $this->configure(['require_approval' => true, 'auth_required' => true]);
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $post = $this->publishedPost();

        $response = $this->actingAs($user)->post(route('comments.store'), [
            'commentable_type' => 'post',
            'commentable_id' => $post->id,
            'body' => 'این یک دیدگاه تستی معتبر است',
            'website' => '',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $post->id,
            'user_id' => $user->id,
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    public function test_xss_payload_is_stored_without_tags(): void
    {
        $this->configure(['require_approval' => false, 'auth_required' => true]);
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $post = $this->publishedPost();

        $this->actingAs($user)->post(route('comments.store'), [
            'commentable_type' => 'post',
            'commentable_id' => $post->id,
            'body' => '<script>alert(1)</script>متن امن',
            'website' => '',
        ])->assertRedirect();

        $comment = Comment::query()->first();
        $this->assertNotNull($comment);
        $this->assertSame('متن امن', $comment->body);
        $this->assertStringNotContainsString('<script>', $comment->body);
    }

    public function test_closed_item_rejects_new_comments(): void
    {
        $this->configure(['auth_required' => true]);
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $post = $this->publishedPost();
        $post->update(['comments_status' => 'closed']);

        $this->actingAs($user)->post(route('comments.store'), [
            'commentable_type' => 'post',
            'commentable_id' => $post->id,
            'body' => 'نباید ذخیره شود',
            'website' => '',
        ])->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_admin_can_approve_comment(): void
    {
        $admin = $this->makeAdmin();
        $post = $this->publishedPost();
        $comment = Comment::factory()->pending()->create([
            'commentable_type' => CmsPost::class,
            'commentable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.comments.approve', $comment))
            ->assertRedirect();

        $this->assertSame(Comment::STATUS_APPROVED, $comment->fresh()->status);
    }

    public function test_honeypot_stores_as_spam_without_error_flash_for_bot(): void
    {
        $this->configure(['require_approval' => false, 'honeypot_enabled' => true]);
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $post = $this->publishedPost();

        $this->actingAs($user)->post(route('comments.store'), [
            'commentable_type' => 'post',
            'commentable_id' => $post->id,
            'body' => 'پیام ربات',
            'website' => 'http://spam.test',
        ])->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'body' => 'پیام ربات',
            'status' => Comment::STATUS_SPAM,
        ]);
    }
}
