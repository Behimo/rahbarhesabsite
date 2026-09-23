<?php

namespace Database\Seeders\Demo\Support;

readonly class BlogCategoryDefinition
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $description,
        public int $sortOrder = 0,
    ) {}
}
