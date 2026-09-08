<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLesson extends Model
{
    protected $fillable = [
        'section_id', 'title', 'slug', 'content', 'video_url', 'video_provider',
        'duration_seconds', 'is_free_preview', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_free_preview' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function course(): Course
    {
        return $this->section->course;
    }
}
