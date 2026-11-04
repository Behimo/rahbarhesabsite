<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ShopProduct;
use Illuminate\Http\Request;

/** @mixin ShopProduct */
class CourseDetailResource extends CourseResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'description' => $this->description,
            'level' => $this->course?->level,
            'duration_minutes' => $this->course?->duration_minutes,
            'what_you_learn' => $this->course?->what_you_learn ?? [],
            'requirements' => $this->course?->requirements ?? [],
            'sections' => SectionResource::collection(
                $this->course?->relationLoaded('sections') ? $this->course->sections : [],
            ),
        ]);
    }
}
