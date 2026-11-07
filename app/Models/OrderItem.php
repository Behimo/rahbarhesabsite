<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'shop_product_id', 'title', 'price', 'quantity', 'metadata'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id')->withTrashed();
    }

    /**
     * Courses the customer actually bought.
     * A stored course_ids list wins over the live catalog, including when that list is empty.
     * Orders created before the snapshot existed fall back to the current product relations.
     *
     * @return Collection<int, Course>
     */
    public function purchasedCourses(): Collection
    {
        $metadata = $this->metadata ?? [];

        if (! array_key_exists('course_ids', $metadata) || ! is_array($metadata['course_ids'])) {
            return $this->product?->relatedCourses() ?? collect();
        }

        $ids = collect($metadata['course_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $courses = Course::query()->whereIn('id', $ids)->get()->keyBy('id');

        return $ids
            ->map(fn (int $id) => $courses->get($id))
            ->filter()
            ->values();
    }
}
