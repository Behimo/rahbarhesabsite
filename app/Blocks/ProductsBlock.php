<?php

namespace App\Blocks;

use App\Models\CmsProduct;

class ProductsBlock extends AbstractBlock
{
    public function type(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return 'لیست محصولات';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => 'محصولات'],
            'limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 4],
        ];
    }

    public function render(array $settings): string
    {
        $products = CmsProduct::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->limit((int) ($settings['limit'] ?? 4))
            ->get();

        return $this->view('blocks.products', [
            'title' => $settings['title'] ?? 'محصولات',
            'products' => $products,
        ]);
    }
}
