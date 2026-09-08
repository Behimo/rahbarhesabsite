<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'shop_product_id', 'instructor_id', 'level', 'duration_minutes',
        'what_you_learn', 'requirements',
    ];

    protected function casts(): array
    {
        return [
            'what_you_learn' => 'array',
            'requirements' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class)->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function lessonsCount(): int
    {
        return CourseLesson::query()
            ->whereIn('section_id', $this->sections()->pluck('id'))
            ->count();
    }
}
