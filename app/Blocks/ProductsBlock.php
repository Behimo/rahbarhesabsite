<?php

namespace App\Blocks;

use App\Models\ShopProduct;

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
        $products = ShopProduct::query()
            ->published()
            ->orderBy('sort_order')
            ->limit((int) ($settings['limit'] ?? 4))
            ->get();

        return $this->blockView('products', [
            'title' => $settings['title'] ?? 'محصولات',
            'products' => $products,
        ]);
    }
}
