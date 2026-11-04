<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use App\Models\SpotplayerLicense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /** @param  iterable<int, Order>  $orders */
    public static function prepareMany(iterable $orders): void
    {
        $orders = Collection::make($orders);
        if ($orders->isEmpty()) {
            return;
        }

        $licenses = SpotplayerLicense::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->get(['id', 'order_id', 'course_id', 'status'])
            ->groupBy('order_id');

        foreach ($orders as $order) {
            $order->setRelation(
                'appLicenses',
                ($licenses->get($order->id) ?? new Collection)->keyBy('course_id'),
            );
            foreach ($order->items as $item) {
                $item->setRelation('order', $order);
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status,
            'channel' => $this->channel,
            'subtotal' => (int) $this->subtotal,
            'discount' => (int) $this->discount,
            'total' => (int) $this->total,
            'currency' => $this->currency,
            'coupon_code' => $this->coupon_code,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
