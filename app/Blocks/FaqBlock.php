<?php

namespace App\Blocks;

use App\Services\HomePageDefaults;
use App\Services\PageRenderContext;

class FaqBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return 'سوالات پرتکرار';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان بخش', 'default' => 'پرتکرارترین سوالات حسابداری'],
            'items' => [
                'type' => 'repeater',
                'label' => 'سوالات',
                'fields' => [
                    'question' => ['type' => 'text', 'label' => 'سؤال'],
                    'answer' => ['type' => 'textarea', 'label' => 'پاسخ'],
                    'href' => ['type' => 'text', 'label' => 'لینک', 'default' => ''],
                ],
            ],
        ];
    }

    public function render(array $settings): string
    {
        $faqs = $this->normalizeFaqs($settings['items'] ?? []);

        if ($faqs === []) {
            $faqs = app(PageRenderContext::class)->get('faqs', app(HomePageDefaults::class)->defaultFaqs());
        }

        if ($faqs === []) {
            return '';
        }

        return $this->blockView('faq', [
            'title' => $settings['title'] ?? 'پرتکرارترین سوالات حسابداری',
            'sectionTitle' => $settings['title'] ?? 'پرتکرارترین سوالات حسابداری',
            'faqs' => $faqs,
            'items' => $faqs,
        ]);
    }

    /** @param  array<int, array<string, string>>  $items */
    private function normalizeFaqs(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => ! empty($item['question'] ?? $item['q'] ?? ''))
            ->map(fn ($item) => [
                'q' => $item['question'] ?? $item['q'] ?? '',
                'a' => $item['answer'] ?? $item['a'] ?? '',
                'href' => $item['href'] ?? route('blog.index'),
            ])
            ->values()
            ->all();
    }
}
