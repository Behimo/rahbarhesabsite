<?php

namespace App\Services;

use App\Models\CmsPage;

class PageBuilderService
{
    public function __construct(
        private HomePageDefaults $homeDefaults,
        private ThemeService $themes,
    ) {}

    /** @return array<string, mixed> */
    public function resolveContent(?CmsPage $page, string $slug = ''): array
    {
        if ($page?->builder_enabled && ! empty($page->builder_content['blocks'])) {
            return $page->builder_content;
        }

        if ($slug === 'home') {
            return $this->homeDefaults->builderContent();
        }

        return ['blocks' => []];
    }

    public function usesFullWidthLayout(?CmsPage $page, string $slug = ''): bool
    {
        if ($slug === 'home') {
            return true;
        }

        return $this->themes->active() === 'rahbarhesab' && $this->shouldRenderBuilder($page, $slug);
    }

    public function shouldRenderBuilder(?CmsPage $page, string $slug = ''): bool
    {
        $content = $this->resolveContent($page, $slug);

        if (($content['blocks'] ?? []) === []) {
            return false;
        }

        return $slug === 'home' || (bool) $page?->builder_enabled;
    }
}
