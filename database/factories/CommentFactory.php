<?php

namespace Database\Factories;

use App\Models\CmsPost;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'author_name' => null,
            'author_email' => null,
            'author_ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'commentable_type' => CmsPost::class,
            'commentable_id' => CmsPost::factory(),
            'parent_id' => null,
            'body' => fake()->sentence(),
            'status' => Comment::STATUS_APPROVED,
            'is_pinned' => false,
            'is_admin_reply' => false,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Comment::STATUS_PENDING,
            'approved_at' => null,
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn () => [
            'status' => Comment::STATUS_SPAM,
            'spam_score' => 80,
            'spam_reasons' => ['blacklist'],
            'approved_at' => null,
        ]);
    }
}
