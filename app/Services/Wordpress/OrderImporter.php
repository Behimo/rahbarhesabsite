<?php

namespace App\Services\Wordpress;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\CourseEnrollmentService;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderImporter extends WordpressImporter
{
    public function countPaidOrders(): int
    {
        return (int) $this->wp()->table('posts')
            ->where('post_type', 'shop_order')
            ->whereIn('post_status', ['wc-completed', 'wc-processing'])
            ->count();
    }

    /**
     * @param  (callable(): void)|null  $onProgress  called once per WP paid order
     * @return array{created: int, enrolled: int, needs_review: int, linked_by_phone: int}
     */
    public function migrateOrders(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $created = 0;
        $enrolled = 0;
        $needsReview = 0;
        $linkedByPhone = 0;

        $usersByWpId = User::query()->whereNotNull('wp_id')->get(['id', 'wp_id', 'mobile', 'phone', 'status'])
            ->keyBy(fn (User $u) => (int) $u->wp_id);
        $usersByMobile = User::query()
            ->where(function ($q) {
                $q->whereNotNull('mobile')->orWhereNotNull('phone');
            })
            ->get(['id', 'wp_id', 'mobile', 'phone', 'status']);
        $mobileIndex = [];
        foreach ($usersByMobile as $u) {
            foreach (array_filter([(string) $u->mobile, (string) $u->phone]) as $m) {
                $mobileIndex[$m] = $u;
            }
        }

        $productsBySlug = ShopProduct::query()->with(['course', 'bundledCourses'])->get()->keyBy('slug');
        $existingOrderWpIds = Order::query()->whereNotNull('wp_id')->pluck('id', 'wp_id');
        $enrollmentService = app(CourseEnrollmentService::class);

        $orders = $this->wp()->table('posts')
            ->where('post_type', 'shop_order')
            ->whereIn('post_status', ['wc-completed', 'wc-processing'])
            ->orderBy('ID')
            ->lazyById(200, 'ID');

        foreach ($orders->chunk(100) as $batch) {
            $orderIds = $batch->pluck('ID')->map(fn ($id) => (int) $id)->all();
            $metaByOrder = $this->postMetaForIds($orderIds);

            $allItems = $this->wp()->table('woocommerce_order_items')
                ->whereIn('order_id', $orderIds)
                ->where('order_item_type', 'line_item')
                ->orderBy('order_item_id')
                ->get()
                ->groupBy('order_id');

            $allItemIds = $allItems->flatten(1)->pluck('order_item_id')->all();
            $itemMetaByItem = $allItemIds === []
                ? collect()
                : $this->wp()->table('woocommerce_order_itemmeta')
                    ->whereIn('order_item_id', $allItemIds)
                    ->get(['order_item_id', 'meta_key', 'meta_value'])
                    ->groupBy('order_item_id')
                    ->map(fn ($rows) => $rows->pluck('meta_value', 'meta_key')->all());

            $wpProductIds = $itemMetaByItem
                ->map(fn (array $values) => (int) ($values['_product_id'] ?? 0))
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            $wpProducts = $wpProductIds === []
                ? collect()
                : $this->wp()->table('posts')->whereIn('ID', $wpProductIds)->get(['ID', 'post_name'])->keyBy('ID');

            foreach ($batch as $wpOrder) {
                $meta = $metaByOrder[$wpOrder->ID] ?? [];
                $wpCustomerId = isset($meta['_customer_user']) ? (int) $meta['_customer_user'] : 0;
                $billingPhoneRaw = (string) ($meta['_billing_phone'] ?? $meta['billing_phone'] ?? '');
                $billingPhone = PhoneNormalizer::isValidIranMobile($billingPhoneRaw)
                    ? PhoneNormalizer::toLocal($billingPhoneRaw)
                    : null;

                $linkedViaPhone = false;
                $user = $wpCustomerId > 0 ? ($usersByWpId->get($wpCustomerId) ?: null) : null;

                if (! $user && $billingPhone) {
                    $user = $mobileIndex[$billingPhone] ?? null;
                    if (! $user && ! $dryRun) {
                        $user = $this->findOrCreateUserForBillingPhone($billingPhone, $meta, false);
                        if ($user) {
                            $mobileIndex[$billingPhone] = $user;
                            if ($user->wp_id) {
                                $usersByWpId->put((int) $user->wp_id, $user);
                            }
                        }
                    } elseif (! $user && $dryRun) {
                        $user = new User(['mobile' => $billingPhone, 'phone' => $billingPhone, 'status' => User::STATUS_ACTIVE]);
                    }
                    $linkedViaPhone = $user !== null;
                }

                $isOrphan = $user === null || (! $dryRun && ! $user->exists);

                if ($dryRun) {
                    if (! $existingOrderWpIds->has($wpOrder->ID)) {
                        $created++;
                    }
                    if ($isOrphan) {
                        $needsReview++;
                    }
                    if ($linkedViaPhone) {
                        $linkedByPhone++;
                    }
                    $onProgress && $onProgress();

                    continue;
                }

                $items = $allItems->get($wpOrder->ID, collect());
                $wasCreated = ! $existingOrderWpIds->has($wpOrder->ID);

                DB::transaction(function () use (
                    $wpOrder,
                    $meta,
                    $user,
                    $items,
                    $itemMetaByItem,
                    $wpProducts,
                    $productsBySlug,
                    $wpCustomerId,
                    $billingPhone,
                    $isOrphan,
                    $enrollmentService,
                    $existingOrderWpIds,
                    &$enrolled
                ): void {
                    $order = Order::query()->firstOrCreate(
                        ['wp_id' => $wpOrder->ID],
                        [
                            'user_id' => $isOrphan ? null : $user->id,
                            'order_number' => 'WP-'.$wpOrder->ID,
                            'status' => Order::STATUS_PAID,
                            'subtotal' => (int) ($meta['_order_total'] ?? 0),
                            'discount' => (int) ($meta['_cart_discount'] ?? 0),
                            'total' => (int) ($meta['_order_total'] ?? 0),
                            'paid_at' => $wpOrder->post_date ? Carbon::parse($wpOrder->post_date) : now(),
                            'needs_review' => $isOrphan,
                            'wp_customer_id' => $wpCustomerId > 0 ? $wpCustomerId : null,
                            'billing_phone' => $billingPhone,
                        ]
                    );

                    $order->forceFill([
                        'user_id' => $isOrphan ? null : $user->id,
                        'needs_review' => $isOrphan,
                        'wp_customer_id' => $wpCustomerId > 0 ? $wpCustomerId : null,
                        'billing_phone' => $billingPhone,
                        'status' => Order::STATUS_PAID,
                        'subtotal' => (int) ($meta['_order_total'] ?? 0),
                        'discount' => (int) ($meta['_cart_discount'] ?? 0),
                        'total' => (int) ($meta['_order_total'] ?? 0),
                        'paid_at' => $wpOrder->post_date ? Carbon::parse($wpOrder->post_date) : ($order->paid_at ?? now()),
                    ])->save();

                    $existingOrderWpIds[$wpOrder->ID] = $order->id;

                    $existingItems = $order->items()->get();
                    $existingWpItemIds = $existingItems
                        ->map(fn (OrderItem $item) => (string) ($item->metadata['wp_order_item_id'] ?? ''))
                        ->filter()
                        ->flip();
                    $legacyMatches = $existingItems->filter(
                        fn (OrderItem $item) => empty($item->metadata['wp_order_item_id'])
                    )->values()->all();

                    foreach ($items as $item) {
                        $sourceMeta = $itemMetaByItem->get($item->order_item_id, []);
                        $wpItemId = (string) $item->order_item_id;
                        $productId = (int) ($sourceMeta['_product_id'] ?? 0);
                        $price = (int) ($sourceMeta['_line_total'] ?? 0);
                        $quantity = max(1, (int) ($sourceMeta['_qty'] ?? 1));
                        $wpProduct = $wpProducts->get($productId);
                        $shopProduct = $wpProduct ? $productsBySlug->get($wpProduct->post_name) : null;

                        if (! $existingWpItemIds->has($wpItemId)) {
                            $legacyIndex = null;
                            foreach ($legacyMatches as $index => $legacy) {
                                if ((int) ($legacy->metadata['wp_product_id'] ?? 0) === $productId
                                    && $legacy->title === $item->order_item_name
                                    && (int) $legacy->price === $price
                                    && (int) $legacy->quantity === $quantity) {
                                    $legacyIndex = $index;
                                    break;
                                }
                            }

                            if ($legacyIndex !== null) {
                                $legacy = $legacyMatches[$legacyIndex];
                                unset($legacyMatches[$legacyIndex]);
                                $legacy->update(['metadata' => array_merge($legacy->metadata ?? [], [
                                    'wp_order_item_id' => $wpItemId,
                                    'wp_product_id' => $productId,
                                ])]);
                            } else {
                                OrderItem::query()->create([
                                    'order_id' => $order->id,
                                    'shop_product_id' => $shopProduct?->id,
                                    'title' => $item->order_item_name,
                                    'price' => $price,
                                    'quantity' => $quantity,
                                    'metadata' => [
                                        'wp_order_item_id' => $wpItemId,
                                        'wp_product_id' => $productId,
                                    ],
                                ]);
                            }
                        }

                        if (! $isOrphan && $user && $shopProduct) {
                            foreach ($shopProduct->relatedCourses() as $course) {
                                $enrollment = $enrollmentService->enrollImported($user, $course, $order);
                                if ($enrollment->wasRecentlyCreated) {
                                    $enrolled++;
                                }
                            }
                        }
                    }
                });

                if ($wasCreated) {
                    $created++;
                }
                if ($isOrphan) {
                    $needsReview++;
                }
                if ($linkedViaPhone) {
                    $linkedByPhone++;
                }

                $onProgress && $onProgress();
            }
        }

        return [
            'created' => $created,
            'enrolled' => $enrolled,
            'needs_review' => $needsReview,
            'linked_by_phone' => $linkedByPhone,
        ];
    }
}
