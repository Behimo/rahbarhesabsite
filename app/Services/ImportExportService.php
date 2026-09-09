<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsSetting;

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
        foreach ($payload['settings'] ?? [] as $key => $value) {
            CmsSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        foreach ($payload['pages'] ?? [] as $page) {
            unset($page['id']);
            CmsPage::query()->updateOrCreate(['slug' => $page['slug']], $page);
        }

        foreach ($payload['posts'] ?? [] as $post) {
            unset($post['id']);
            CmsPost::query()->updateOrCreate(['slug' => $post['slug']], $post);
        }
    }
}
