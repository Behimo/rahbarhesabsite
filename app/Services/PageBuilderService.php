<?php

namespace App\Services;

use App\Models\CmsPage;

class PageBuilderService
{
    /** @return array<string, mixed> */
    public function resolveContent(?CmsPage $page): array
    {
        if ($page?->builder_enabled && ! empty($page->builder_content['blocks'])) {
            return $page->builder_content;
        }

        return ['blocks' => []];
    }

    public function shouldRenderBuilder(?CmsPage $page): bool
    {
        return ($this->resolveContent($page)['blocks'] ?? []) !== [];
    }
}
