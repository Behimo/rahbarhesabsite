<?php

namespace Tests\Unit;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    public function test_flush_tag_refreshes_values_without_calling_cache_tags(): void
    {
        config(['cache.default' => 'array']);
        Cache::setDefaultDriver('array');

        $cache = app(CacheService::class);
        $reads = 0;
        $load = function () use (&$reads) {
            $reads++;

            return 'value-'.$reads;
        };

        $this->assertSame('value-1', $cache->remember('menu', 60, $load, CacheService::TAG_MENUS));
        $this->assertSame('value-1', $cache->remember('menu', 60, $load, CacheService::TAG_MENUS));

        $cache->flushTag(CacheService::TAG_MENUS);

        $this->assertSame('value-2', $cache->remember('menu', 60, $load, CacheService::TAG_MENUS));
        $this->assertSame(2, $reads);
    }
}
