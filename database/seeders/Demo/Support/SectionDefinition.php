<?php

namespace Database\Seeders\Demo\Support;

readonly class SectionDefinition
{
    /**
     * @param  list<LessonDefinition>  $lessons
     */
    public function __construct(
        public string $title,
        public array $lessons,
    ) {}
}
