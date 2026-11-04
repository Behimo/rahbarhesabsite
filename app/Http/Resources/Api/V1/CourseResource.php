<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ShopProduct;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopProduct */
class CourseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course?->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'type' => $this->type,
            'price' => (int) $this->price,
            'sale_price' => $this->sale_price === null ? null : (int) $this->sale_price,
            'payable' => $this->effectivePrice(),
            'currency' => Money::STORE_CURRENCY,
            'image' => $this->featured_image,
            'is_owned' => (bool) $this->is_owned,
            'instructor' => $this->course?->instructor ? [
                'name' => $this->course->instructor->displayName(),
            ] : null,
        ];
    }
}
