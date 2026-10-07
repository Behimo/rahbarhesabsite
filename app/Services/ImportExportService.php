<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportExportService
{
    public function export(): array
    {
        return [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'settings' => CmsSetting::query()->pluck('value', 'key')->all(),
            'pages' => CmsPage::query()->get()->toArray(),
            'posts' => CmsPost::query()->get()->toArray(),
        ];
    }

    public function import(array $payload): void
    {
        $settings = $payload['settings'] ?? [];
        $pages = $payload['pages'] ?? [];
        $posts = $payload['posts'] ?? [];

        if (! is_array($settings) || ! is_array($pages) || ! is_array($posts)) {
            throw new \InvalidArgumentException('ساختار فایل نامعتبر است.');
        }

        foreach ($pages as $page) {
            if (! is_array($page) || ! filled($page['slug'] ?? null)) {
                throw new \InvalidArgumentException('هر صفحه باید نامک داشته باشد.');
            }
        }

        foreach ($posts as $post) {
            if (! is_array($post) || ! filled($post['slug'] ?? null)) {
                throw new \InvalidArgumentException('هر مقاله باید نامک داشته باشد.');
            }
        }

        DB::transaction(function () use ($settings, $pages, $posts) {
            foreach ($settings as $key => $value) {
                if (! is_string($key) || $key === '') {
                    throw new \InvalidArgumentException('کلید تنظیمات نامعتبر است.');
                }

                CmsSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => is_scalar($value) || $value === null ? $value : json_encode($value)],
                );
            }

            foreach ($pages as $page) {
                unset($page['id'], $page['created_at'], $page['updated_at'], $page['deleted_at']);
                CmsPage::query()->updateOrCreate(['slug' => $page['slug']], $page);
            }

            foreach ($posts as $post) {
                unset($post['id'], $post['created_at'], $post['updated_at'], $post['deleted_at']);
                CmsPost::query()->updateOrCreate(['slug' => $post['slug']], $post);
            }
        });

        foreach (array_keys($settings) as $key) {
            if (is_string($key) && $key !== '') {
                Cache::forget('cms_setting.'.$key);
            }
        }

        app(CacheService::class)->flushContent();
        app(SiteDataService::class)->clearCache();
        app(HomeContentService::class)->clearCache();
    }
}
