<?php

namespace App\Services;

use App\Models\CmsRedirect;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WordpressMigrationService
{
    public function connectionReady(): bool
    {
        if (! config('database.connections.wordpress.database')) {
            return false;
        }

        try {
            DB::connection('wordpress')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function migrateUsers(bool $dryRun = false): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $users = $this->wp()->table('users')->orderBy('ID')->lazyById(250, 'ID')->chunk(250);

        foreach ($users as $batch) {
            $userMeta = $this->userMetaForIds($batch->pluck('ID')->all());

            foreach ($batch as $row) {
                $meta = $userMeta[$row->ID] ?? [];
                $mobile = $this->resolveMobile($meta, (string) $row->user_login);

                if (! $mobile) {
                    $skipped++;

                    continue;
                }

                $existing = User::query()->where('wp_id', $row->ID)->orWhere('mobile', $mobile)->orWhere('phone', $mobile)->first();

                $payload = [
                    'wp_id' => $row->ID,
                    'name' => trim(($meta['first_name'] ?? '').' '.($meta['last_name'] ?? '')) ?: ($row->display_name ?: $row->user_login),
                    'first_name' => $meta['first_name'] ?? null,
                    'last_name' => $meta['last_name'] ?? null,
                    'email' => $row->user_email ?: null,
                    'phone' => $mobile,
                    'mobile' => $mobile,
                    'status' => 'active',
                ];

                if ($dryRun) {
                    $existing ? $updated++ : $created++;

                    continue;
                }

                if ($existing) {
                    $existing->fill($payload)->save();
                    $existing->ensureSiteRole();
                    $this->storeWordpressPassword($existing->id, (string) $row->user_pass);
                    $updated++;
                } else {
                    $user = User::query()->create($payload + ['password' => Str::random(32)]);
                    $user->ensureSiteRole();
                    $this->storeWordpressPassword($user->id, (string) $row->user_pass);
                    $created++;
                }
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    public function migrateCourses(bool $dryRun = false): array
    {
        $created = 0;
        $updated = 0;

        $products = $this->wp()->table('posts')
            ->where('post_type', 'product')
            ->whereIn('post_status', ['publish', 'private'])
            ->orderBy('ID')
            ->lazyById(250, 'ID');

        foreach ($products->chunk(250) as $batch) {
            $postMeta = $this->postMetaForIds($batch->pluck('ID')->all());

            foreach ($batch as $post) {
                $meta = $postMeta[$post->ID] ?? [];
                $slug = $post->post_name ?: Str::slug($post->post_title).'-'.$post->ID;
                $price = (int) ($meta['_price'] ?? $meta['_regular_price'] ?? 0);
                $sale = isset($meta['_sale_price']) && $meta['_sale_price'] !== '' ? (int) $meta['_sale_price'] : null;

                if ($dryRun) {
                    ShopProduct::query()->where('slug', $slug)->exists() ? $updated++ : $created++;

                    continue;
                }

                $product = ShopProduct::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $post->post_title,
                        'description' => $post->post_content ?: $post->post_excerpt,
                        'price' => $price,
                        'sale_price' => $sale,
                        'type' => ShopProduct::TYPE_COURSE,
                        'is_published' => $post->post_status === 'publish',
                    ]
                );

                Course::query()->updateOrCreate(
                    ['shop_product_id' => $product->id],
                    [
                        'spotplayer_course_id' => $meta['_spotplayer_course'] ?? $meta['spotplayer_course'] ?? null,
                        'level' => 'intermediate',
                    ]
                );

                $this->ensureRedirect('/product/'.$post->post_name, '/courses/'.$slug);
                $this->ensureRedirect('/?p='.$post->ID, '/courses/'.$slug);

                $product->wasRecentlyCreated ? $created++ : $updated++;
            }
        }

        return compact('created', 'updated');
    }

    public function migrateOrders(bool $dryRun = false): array
    {
        $created = 0;
        $enrolled = 0;

        $orders = $this->wp()->table('posts')
            ->where('post_type', 'shop_order')
            ->whereIn('post_status', ['wc-completed', 'wc-processing'])
            ->orderBy('ID')
            ->lazyById(250, 'ID');

        foreach ($orders as $wpOrder) {
            $meta = $this->postMetaForIds([(int) $wpOrder->ID])[$wpOrder->ID] ?? [];
            $user = isset($meta['_customer_user']) ? User::query()->where('wp_id', (int) $meta['_customer_user'])->first() : null;

            if (! $user) {
                continue;
            }

            if ($dryRun) {
                if (! Order::query()->where('wp_id', $wpOrder->ID)->exists()) {
                    $created++;
                }

                continue;
            }

            $items = $this->wp()->table('woocommerce_order_items')
                ->where('order_id', $wpOrder->ID)
                ->where('order_item_type', 'line_item')
                ->orderBy('order_item_id')
                ->get();

            $itemIds = $items->pluck('order_item_id');
            $itemMeta = $itemIds->isEmpty()
                ? collect()
                : $this->wp()->table('woocommerce_order_itemmeta')
                    ->whereIn('order_item_id', $itemIds)
                    ->get(['order_item_id', 'meta_key', 'meta_value'])
                    ->groupBy('order_item_id')
                    ->map(fn ($rows) => $rows->pluck('meta_value', 'meta_key')->all());

            $productIds = $itemMeta->map(fn (array $values) => (int) ($values['_product_id'] ?? 0))
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values();
            $wpProducts = $productIds->isEmpty()
                ? collect()
                : $this->wp()->table('posts')->whereIn('ID', $productIds)->get(['ID', 'post_name'])->keyBy('ID');
            $productsBySlug = $wpProducts->pluck('post_name')->filter()->unique()->isEmpty()
                ? collect()
                : ShopProduct::query()->whereIn('slug', $wpProducts->pluck('post_name')->filter()->unique())->get()->keyBy('slug');

            $wasCreated = ! Order::query()->where('wp_id', $wpOrder->ID)->exists();
            DB::transaction(function () use ($wpOrder, $meta, $user, $items, $itemMeta, $wpProducts, $productsBySlug, &$enrolled): void {
                $order = Order::query()->firstOrCreate(
                    ['wp_id' => $wpOrder->ID],
                    [
                        'user_id' => $user->id,
                        'order_number' => 'WP-'.$wpOrder->ID,
                        'status' => Order::STATUS_PAID,
                        'subtotal' => (int) ($meta['_order_total'] ?? 0),
                        'discount' => (int) ($meta['_cart_discount'] ?? 0),
                        'total' => (int) ($meta['_order_total'] ?? 0),
                        'paid_at' => $wpOrder->post_date ? Carbon::parse($wpOrder->post_date) : now(),
                    ]
                );

                $existingItems = $order->items()->get();
                $existingWpItemIds = $existingItems
                    ->map(fn (OrderItem $item) => (string) ($item->metadata['wp_order_item_id'] ?? ''))
                    ->filter()
                    ->flip();
                $legacyMatches = $existingItems->filter(
                    fn (OrderItem $item) => empty($item->metadata['wp_order_item_id'])
                )->values()->all();

                foreach ($items as $item) {
                    $sourceMeta = $itemMeta->get($item->order_item_id, []);
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

                    if ($shopProduct) {
                        foreach ($shopProduct->relatedCourses() as $course) {
                            $hadEnrollment = CourseEnrollment::query()
                                ->where('user_id', $user->id)
                                ->where('course_id', $course->id)
                                ->exists();
                            app(OrderService::class)->enrollUser($user, $course, $order, CourseEnrollment::SOURCE_PURCHASE);
                            if (! $hadEnrollment) {
                                $enrolled++;
                            }
                        }
                    }
                }
            });

            if ($wasCreated) {
                $created++;
            }
        }

        return compact('created', 'enrolled');
    }

    public function audit(): array
    {
        return [
            'wp_users' => $this->wp()->table('users')->count(),
            'local_users_with_wp_id' => User::query()->whereNotNull('wp_id')->count(),
            'wp_products' => $this->wp()->table('posts')->where('post_type', 'product')->whereIn('post_status', ['publish', 'private'])->count(),
            'local_courses' => Course::query()->count(),
            'wp_paid_orders' => $this->wp()->table('posts')->where('post_type', 'shop_order')->whereIn('post_status', ['wc-completed', 'wc-processing'])->count(),
            'local_wp_orders' => Order::query()->whereNotNull('wp_id')->count(),
            'local_enrollments' => CourseEnrollment::query()->count(),
        ];
    }

    private function wp()
    {
        return DB::connection('wordpress');
    }

    /** @param array<int, int|string> $userIds
     *  @return array<int|string, array<string, mixed>>
     */
    private function userMetaForIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return $this->wp()->table('usermeta')
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'meta_key', 'meta_value'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('meta_value', 'meta_key')->all())
            ->all();
    }

    /** @param array<int, int|string> $postIds
     *  @return array<int|string, array<string, mixed>>
     */
    private function postMetaForIds(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        return $this->wp()->table('postmeta')
            ->whereIn('post_id', $postIds)
            ->get(['post_id', 'meta_key', 'meta_value'])
            ->groupBy('post_id')
            ->map(fn ($rows) => $rows->pluck('meta_value', 'meta_key')->all())
            ->all();
    }

    private function resolveMobile(array $meta, string $fallback): ?string
    {
        foreach (['loginx_phone', 'digits_phone', 'billing_phone', 'phone'] as $key) {
            if (! empty($meta[$key]) && PhoneNormalizer::isValidIranMobile((string) $meta[$key])) {
                return PhoneNormalizer::toLocal((string) $meta[$key]);
            }
        }

        if (PhoneNormalizer::isValidIranMobile($fallback)) {
            return PhoneNormalizer::toLocal($fallback);
        }

        return null;
    }

    private function storeWordpressPassword(int $userId, string $hash): void
    {
        if ($hash === '') {
            return;
        }

        DB::table('users')->where('id', $userId)->update([
            'password' => $hash,
            'is_wp_password' => true,
        ]);
    }

    private function ensureRedirect(string $from, string $to): void
    {
        CmsRedirect::query()->updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => 301, 'is_active' => true]
        );
    }
}
