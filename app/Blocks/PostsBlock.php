<?php

namespace App\Blocks;

use App\Models\CmsPost;

class PostsBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'posts';
    }

    public function label(): string
    {
        return 'لیست نوشته‌ها';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => 'آخرین نوشته‌ها'],
            'limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 3],
        ];
    }

    public function render(array $settings): string
    {
        $posts = CmsPost::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->limit((int) ($settings['limit'] ?? 3))
            ->get();

        return $this->view('blocks.posts', [
            'title' => $settings['title'] ?? 'آخرین نوشته‌ها',
            'posts' => $posts,
        ]);
    }
}
