<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->shop_product_id,
            'title' => $this->title,
            'price' => (int) $this->price,
            'quantity' => (int) $this->quantity,
            'license_status' => $this->licenseStatus(),
        ];
    }

    private function licenseStatus(): ?string
    {
        if (! $this->relationLoaded('order')) {
            return null;
        }

        $order = $this->getRelation('order');
        if (! $order->relationLoaded('appLicenses')) {
            return null;
        }

        $courseIds = $this->metadata['course_ids'] ?? [];
        if (! is_array($courseIds) || $courseIds === []) {
            return null;
        }

        $licenses = $order->getRelation('appLicenses');
        $statuses = collect($courseIds)->map(fn ($id) => $licenses->get((int) $id)?->status ?? 'pending');

        foreach (['revoked', 'failed', 'pending'] as $status) {
            if ($statuses->contains($status)) {
                return $status;
            }
        }

        return $statuses->every(fn ($status) => $status === 'issued') ? 'issued' : 'pending';
    }
}
