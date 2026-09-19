<?php

namespace App\Blocks;

use App\Models\CmsPost;
use App\Services\HomePageDefaults;
use App\Services\PageRenderContext;

class PostsBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'posts';
    }

    public function label(): string
    {
        return 'اخبار و بخشنامه‌ها';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => 'اخبار و بخشنامه های جدید'],
            'limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 8],
        ];
    }

    public function render(array $settings): string
    {
        $limit = (int) ($settings['limit'] ?? 8);
        $context = app(PageRenderContext::class);

        $posts = CmsPost::query()
            ->published()
            ->with('category')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this->blockView('posts', [
            'title' => $settings['title'] ?? 'اخبار و بخشنامه های جدید',
            'sectionTitle' => $settings['title'] ?? 'اخبار و بخشنامه های جدید',
            'posts' => $posts,
            'latestPosts' => $posts->isNotEmpty() ? $posts : $context->get('latestPosts', collect()),
            'fallbackNews' => $context->get('fallbackNews', app(HomePageDefaults::class)->defaultFallbackNews()),
        ]);
    }
}
