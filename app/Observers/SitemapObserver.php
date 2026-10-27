<?php

namespace App\Observers;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class SitemapObserver
{
    public function __construct(private CacheService $cache) {}

    public function created(Model $model): void
    {
        $this->cache->forgetSitemaps();
    }

    public function saved(Model $model): void
    {
        // Counters are not content edits and must not churn sitemap caches.
        if (array_diff(array_keys($model->getChanges()), ['views', 'views_count', 'updated_at'])) {
            $this->cache->forgetSitemaps();
        }
    }

    public function deleted(Model $model): void
    {
        $this->cache->forgetSitemaps();
    }

    public function restored(Model $model): void
    {
        $this->cache->forgetSitemaps();
    }
}
