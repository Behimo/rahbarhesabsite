<?php

namespace Database\Seeders\Demo\Support;

readonly class BlogPostDefinition
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $excerpt,
        public string $body,
        public string $categorySlug,
        public string $author = 'راهبر حساب',
        public int $views = 0,
        public int $daysAgo = 1,
    ) {}
}
