<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CourseEnrollment */
class EnrollmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $license = $this->relationLoaded('license') ? $this->getRelation('license') : null;

        return [
            'course_id' => $this->course_id,
            'title' => $this->course?->product?->title,
            'slug' => $this->course?->product?->slug,
            'status' => $this->isActive() ? 'active' : $this->status,
            'enrolled_at' => $this->enrolled_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'progress_percent' => (int) $this->progress_percent,
            'license_status' => $license?->status,
        ];
    }
}
