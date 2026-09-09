<?php

namespace Database\Seeders;

use App\Models\CmsMenu;
use App\Models\CmsMenuItem;
use App\Models\CmsTheme;
use App\Services\TaxonomyService;
use App\Services\ThemeService;
use Illuminate\Database\Seeder;

class CmsExtensionsSeeder extends Seeder
{
    public function run(): void
    {
        app(ThemeService::class)->discover();
        app(TaxonomyService::class)->ensureDefaults();

        CmsTheme::query()->updateOrCreate(
            ['slug' => 'rahbarhesab'],
            ['name' => 'راهبر حساب', 'version' => '1.0.0', 'is_active' => true]
        );

        $menu = CmsMenu::query()->firstOrCreate(
            ['slug' => 'primary'],
            ['name' => 'منوی اصلی', 'location' => 'primary']
        );

        if ($menu->allItems()->count() === 0) {
            $items = [
                ['label' => 'خانه', 'type' => 'route', 'route_name' => 'home'],
                ['label' => 'دوره‌ها', 'type' => 'route', 'route_name' => 'courses.index'],
                ['label' => 'بلاگ و اخبار', 'type' => 'route', 'route_name' => 'blog.index'],
                ['label' => 'درباره ما', 'type' => 'route', 'route_name' => 'about'],
                ['label' => 'تماس', 'type' => 'route', 'route_name' => 'contact'],
            ];

            foreach ($items as $i => $item) {
                CmsMenuItem::query()->create([
                    'menu_id' => $menu->id,
                    'label' => $item['label'],
                    'type' => $item['type'],
                    'route_name' => $item['route_name'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
