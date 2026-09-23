<?php

namespace Database\Seeders\Demo\Support;

readonly class LessonDefinition
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $content,
        public int $durationSeconds = 600,
        public bool $isFreePreview = false,
    ) {}
}
