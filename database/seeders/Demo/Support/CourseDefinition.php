<?php

namespace Database\Seeders\Demo\Support;

readonly class CourseDefinition
{
    /**
     * @param  list<string>  $whatYouLearn
     * @param  list<string>  $requirements
     * @param  list<SectionDefinition>  $sections
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $subtitle,
        public string $description,
        public int $price,
        public ?int $salePrice,
        public string $level,
        public int $durationMinutes,
        public array $whatYouLearn,
        public array $requirements,
        public array $sections,
        public int $sortOrder = 0,
    ) {}
}
