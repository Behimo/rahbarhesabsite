<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CourseLesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CourseLesson */
class LessonResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'duration_seconds' => $this->duration_seconds,
            'is_free_preview' => (bool) $this->is_free_preview,
        ];
    }
}
