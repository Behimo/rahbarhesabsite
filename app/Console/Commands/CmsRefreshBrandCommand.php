<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use App\Models\CmsProduct;
use App\Services\CacheService;
use App\Services\SiteDataService;
use Illuminate\Console\Command;

class CmsRefreshBrandCommand extends Command
{
    protected $signature = 'cms:refresh-brand';

    protected $description = 'Replace legacy Bisan page/product defaults with Rahbar Hesab CMS content';

    public function handle(SiteDataService $siteData, CacheService $cache): int
    {
        foreach ($siteData->rahbarPageMeta() as $slug => $meta) {
            CmsPage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $meta['title'],
                    'meta_title' => $meta['title'],
                    'meta_description' => $meta['description'],
                    'meta_keywords' => $meta['keywords'] ?? null,
                    'robots' => 'index, follow',
                    'is_published' => true,
                    'is_system' => in_array($slug, ['home', 'about', 'contact'], true),
                    'template' => 'system',
                    'status' => 'published',
                    'sort_order' => match ($slug) {
                        'home' => 1,
                        'about' => 2,
                        'contact' => 3,
                        default => 99,
                    },
                ]
            );
        }

        CmsPage::query()->where('slug', 'why-bisan')->delete();
        CmsPage::query()->where('slug', 'services')->delete();
        CmsPage::query()->where('slug', 'products')->where('is_system', true)->delete();

        CmsProduct::query()
            ->whereIn('slug', ['rahbar', 'nojaro', 'vileo', 'wordpress-plugin'])
            ->delete();

        $cache->flushContent();
        $siteData->clearCache();

        $this->info('Rahbar Hesab branding applied to CMS pages.');

        return self::SUCCESS;
    }
}
