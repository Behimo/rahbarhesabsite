<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CourseSection */
class SectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'lessons' => LessonResource::collection($this->whenLoaded('lessons')),
        ];
    }
}
