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

        foreach ($this->wp()->table('users')->orderBy('ID')->cursor() as $row) {
            $meta = $this->userMeta((int) $row->ID);
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
                'role' => User::ROLE_USER,
            ];

            if ($dryRun) {
                $existing ? $updated++ : $created++;
                continue;
            }

            if ($existing) {
                $existing->fill($payload)->save();
                $this->storeWordpressPassword($existing->id, (string) $row->user_pass);
                $updated++;
            } else {
                $user = User::query()->create($payload + ['password' => Str::random(32)]);
                $this->storeWordpressPassword($user->id, (string) $row->user_pass);
                $created++;
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
            ->get();

        foreach ($products as $post) {
            $meta = $this->postMeta((int) $post->ID);
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
            ->get();

        foreach ($orders as $wpOrder) {
            if (Order::query()->where('wp_id', $wpOrder->ID)->exists()) {
                continue;
            }

            $meta = $this->postMeta((int) $wpOrder->ID);
            $user = isset($meta['_customer_user']) ? User::query()->where('wp_id', (int) $meta['_customer_user'])->first() : null;

            if (! $user) {
                continue;
            }

            if ($dryRun) {
                $created++;
                continue;
            }

            $order = Order::query()->create([
                'wp_id' => $wpOrder->ID,
                'user_id' => $user->id,
                'order_number' => 'WP-'.$wpOrder->ID,
                'status' => Order::STATUS_PAID,
                'subtotal' => (int) ($meta['_order_total'] ?? 0),
                'discount' => (int) ($meta['_cart_discount'] ?? 0),
                'total' => (int) ($meta['_order_total'] ?? 0),
                'paid_at' => $wpOrder->post_date ? \Carbon\Carbon::parse($wpOrder->post_date) : now(),
            ]);

            $items = $this->wp()->table('woocommerce_order_items')
                ->where('order_id', $wpOrder->ID)
                ->where('order_item_type', 'line_item')
                ->get();

            foreach ($items as $item) {
                $itemMeta = $this->wp()->table('woocommerce_order_itemmeta')
                    ->where('order_item_id', $item->order_item_id)
                    ->pluck('meta_value', 'meta_key');

                $productId = (int) ($itemMeta['_product_id'] ?? 0);
                $shopProduct = $this->shopProductFromWp($productId);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'shop_product_id' => $shopProduct?->id,
                    'title' => $item->order_item_name,
                    'price' => (int) ($itemMeta['_line_total'] ?? 0),
                    'quantity' => (int) ($itemMeta['_qty'] ?? 1),
                    'metadata' => ['wp_product_id' => $productId],
                ]);

                if ($shopProduct) {
                    foreach ($shopProduct->relatedCourses() as $course) {
                        app(OrderService::class)->enrollUser($user, $course, $order, CourseEnrollment::SOURCE_PURCHASE);
                        $enrolled++;
                    }
                }
            }

            $created++;
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

    private function userMeta(int $userId): array
    {
        return $this->wp()->table('usermeta')->where('user_id', $userId)->pluck('meta_value', 'meta_key')->all();
    }

    private function postMeta(int $postId): array
    {
        return $this->wp()->table('postmeta')->where('post_id', $postId)->pluck('meta_value', 'meta_key')->all();
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

    private function shopProductFromWp(int $wpProductId): ?ShopProduct
    {
        if ($wpProductId <= 0) {
            return null;
        }

        $post = $this->wp()->table('posts')->where('ID', $wpProductId)->first();
        if (! $post) {
            return null;
        }

        return ShopProduct::query()->where('slug', $post->post_name)->first();
    }

    private function ensureRedirect(string $from, string $to): void
    {
        CmsRedirect::query()->updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => 301, 'is_active' => true]
        );
    }
}
