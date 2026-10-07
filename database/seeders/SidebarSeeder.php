<?php

namespace Database\Seeders;

use App\Models\CmsSidebar;
use Illuminate\Database\Seeder;

class SidebarSeeder extends Seeder
{
    public function run(): void
    {
        $sidebar = CmsSidebar::query()->firstOrCreate(
            ['name' => 'سایدبار پیش‌فرض مقالات'],
            [
                'title' => 'آخرین دوره‌ها',
                'is_active' => true,
                'target_mode' => CmsSidebar::MODE_PAGES,
                'pages' => ['blog.index'],
                'source' => CmsSidebar::SOURCE_COURSE,
                'selection' => CmsSidebar::SELECTION_LATEST,
                'limit' => 5,
                'sort_order' => 0,
            ]
        );

        if ($sidebar->pages === ['blog.show']) {
            $sidebar->update(['pages' => ['blog.index']]);
        }
    }
}
