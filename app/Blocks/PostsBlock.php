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
            'items' => [
                'type' => 'repeater',
                'label' => 'اخبار جایگزین (وقتی مطلبی نیست)',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'عنوان'],
                    'subtitle' => ['type' => 'text', 'label' => 'زیرعنوان'],
                    'href' => ['type' => 'text', 'label' => 'لینک'],
                ],
            ],
        ];
    }

    public function render(array $settings): string
    {
        $limit = (int) ($settings['limit'] ?? 8);
        $context = app(PageRenderContext::class);

        $posts = CmsPost::query()
            ->published()
            ->with('categories')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $fallback = array_key_exists('items', $settings)
            ? $this->normalizeNews($settings['items'] ?? [])
            : $context->get('fallbackNews', app(HomePageDefaults::class)->defaultFallbackNews());

        return $this->blockView('posts', [
            'title' => $settings['title'] ?? 'اخبار و بخشنامه های جدید',
            'sectionTitle' => $settings['title'] ?? 'اخبار و بخشنامه های جدید',
            'posts' => $posts,
            'latestPosts' => $posts->isNotEmpty() ? $posts : $context->get('latestPosts', collect()),
            'fallbackNews' => $fallback,
        ]);
    }

    /** @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, string>>
     */
    private function normalizeNews(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => filled($item['title'] ?? null))
            ->map(fn ($item) => [
                'title' => (string) ($item['title'] ?? ''),
                'subtitle' => (string) ($item['subtitle'] ?? ''),
                'href' => (string) ($item['href'] ?? '/blog'),
            ])
            ->values()
            ->all();
    }
}
