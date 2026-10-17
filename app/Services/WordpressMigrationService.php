<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CmsPost;
use App\Models\CmsRedirect;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
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

    public function countUsers(): int
    {
        return (int) $this->wp()->table('users')->count();
    }

    public function countProducts(): int
    {
        return (int) $this->wp()->table('posts')
            ->where('post_type', 'product')
            ->whereIn('post_status', ['publish', 'private'])
            ->count();
    }

    public function countPaidOrders(): int
    {
        return (int) $this->wp()->table('posts')
            ->where('post_type', 'shop_order')
            ->whereIn('post_status', ['wc-completed', 'wc-processing'])
            ->count();
    }

    public function countCategories(): int
    {
        return (int) $this->wp()->table('term_taxonomy')
            ->whereIn('taxonomy', ['product_cat', 'category'])
            ->count();
    }

    public function countPosts(): int
    {
        return (int) $this->wp()->table('posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();
    }

    /**
     * @param  (callable(): void)|null  $onProgress
     * @return array{created: int, updated: int, skipped: int}
     */
    public function migrateCategories(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ([
            'product_cat' => Category::TYPE_PRODUCT,
            'category' => Category::TYPE_POST,
        ] as $taxonomy => $type) {
            $termIdMap = [];

            $terms = $this->wp()->table('term_taxonomy as tt')
                ->join('terms as t', 't.term_id', '=', 'tt.term_id')
                ->where('tt.taxonomy', $taxonomy)
                ->orderBy('tt.parent')
                ->orderBy('t.term_id')
                ->get([
                    't.term_id',
                    't.name',
                    't.slug',
                    'tt.parent',
                    'tt.description',
                ]);

            foreach ($terms as $term) {
                $slug = $this->normalizeWpSlug((string) $term->slug);
                $name = trim((string) $term->name);

                if ($slug === '' || $this->isSkippedCategorySlug($slug) || $name === '') {
                    $skipped++;
                    $onProgress && $onProgress();

                    continue;
                }

                $parentId = null;
                if ((int) $term->parent > 0) {
                    $parentId = $termIdMap[(int) $term->parent] ?? null;
                }

                if ($dryRun) {
                    Category::query()
                        ->where('type', $type)
                        ->where(function ($q) use ($slug, $parentId) {
                            $q->where('slug', $slug);
                            $parentId
                                ? $q->where('parent_id', $parentId)
                                : $q->whereNull('parent_id');
                        })
                        ->exists() ? $updated++ : $created++;
                    $onProgress && $onProgress();

                    continue;
                }

                $category = Category::withTrashed()
                    ->where('type', $type)
                    ->where(function ($q) use ($slug, $parentId) {
                        $q->where('slug', $slug);
                        $parentId
                            ? $q->where('parent_id', $parentId)
                            : $q->whereNull('parent_id');
                    })
                    ->first();

                if (! $category) {
                    $category = Category::withTrashed()
                        ->where('type', $type)
                        ->where('full_slug', $slug)
                        ->whereNull('parent_id')
                        ->first();
                }

                $payload = [
                    'parent_id' => $parentId,
                    'type' => $type,
                    'name' => mb_substr($name, 0, 191),
                    'slug' => mb_substr($slug, 0, 191),
                    'description' => $term->description ?: null,
                    'is_active' => true,
                ];

                if ($category) {
                    if ($category->trashed()) {
                        $category->restore();
                    }
                    $category->fill($payload)->save();
                    $updated++;
                } else {
                    $category = Category::query()->create($payload);
                    $created++;
                }

                $termIdMap[(int) $term->term_id] = $category->id;
                $onProgress && $onProgress();
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @param  (callable(): void)|null  $onProgress  called once per WP user row
     */
    public function migrateUsers(bool $dryRun = false, ?callable $onProgress = null): array
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
                    $onProgress && $onProgress();

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
                    $onProgress && $onProgress();

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

                $onProgress && $onProgress();
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @param  (callable(): void)|null  $onProgress  called once per WP product
     */
    public function migrateCourses(bool $dryRun = false, ?callable $onProgress = null): array
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
                $rawSlug = (string) ($post->post_name ?: '');
                $slug = $this->normalizeWpSlug($rawSlug) ?: (Str::slug($post->post_title).'-'.$post->ID);
                $price = (int) ($meta['_price'] ?? $meta['_regular_price'] ?? 0);
                $sale = isset($meta['_sale_price']) && $meta['_sale_price'] !== '' ? (int) $meta['_sale_price'] : null;
                $spotCourseId = $this->normalizeSpotplayerCourseId(
                    $meta['_spotplayer_course'] ?? $meta['spotplayer_course'] ?? null
                );

                if ($dryRun) {
                    ShopProduct::query()
                        ->where('slug', $slug)
                        ->orWhere('slug', $rawSlug)
                        ->exists() ? $updated++ : $created++;
                    $onProgress && $onProgress();

                    continue;
                }

                $product = ShopProduct::query()
                    ->where('slug', $slug)
                    ->orWhere('slug', $rawSlug)
                    ->first();

                if ($product) {
                    $product->fill([
                        'slug' => $slug,
                        'title' => $post->post_title,
                        'description' => $post->post_content ?: $post->post_excerpt,
                        'price' => $price,
                        'sale_price' => $sale,
                        'type' => ShopProduct::TYPE_COURSE,
                        'is_published' => $post->post_status === 'publish',
                    ])->save();
                } else {
                    $product = ShopProduct::query()->create([
                        'slug' => $slug,
                        'title' => $post->post_title,
                        'description' => $post->post_content ?: $post->post_excerpt,
                        'price' => $price,
                        'sale_price' => $sale,
                        'type' => ShopProduct::TYPE_COURSE,
                        'is_published' => $post->post_status === 'publish',
                    ]);
                }

                Course::query()->updateOrCreate(
                    ['shop_product_id' => $product->id],
                    [
                        'spotplayer_course_id' => $spotCourseId,
                        'level' => 'intermediate',
                    ]
                );

                $this->ensureRedirect('/product/'.$slug, '/courses/'.$slug);
                // Encoded WP slugs often exceed cms_redirects.from_path (varchar 191); skip if too long.
                if ($rawSlug !== '' && $rawSlug !== $slug) {
                    $this->ensureRedirect('/product/'.$rawSlug, '/courses/'.$slug);
                }
                $this->ensureRedirect('/?p='.$post->ID, '/courses/'.$slug);

                $this->syncModelCategories($product, (int) $post->ID, 'product_cat', Category::TYPE_PRODUCT);

                $product->wasRecentlyCreated ? $created++ : $updated++;
                $onProgress && $onProgress();
            }
        }

        return compact('created', 'updated');
    }

    /**
     * @param  (callable(): void)|null  $onProgress  called once per published WP post
     * @return array{created: int, updated: int}
     */
    public function migratePosts(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;

        $posts = $this->wp()->table('posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->lazyById(250, 'ID');

        foreach ($posts->chunk(250) as $batch) {
            $postMeta = $this->postMetaForIds($batch->pluck('ID')->all());
            $authors = $this->wp()->table('users')
                ->whereIn('ID', $batch->pluck('post_author')->filter()->unique()->all())
                ->get(['ID', 'display_name'])
                ->keyBy('ID');

            foreach ($batch as $post) {
                $meta = $postMeta[$post->ID] ?? [];
                $rawSlug = (string) ($post->post_name ?: '');
                $slug = $this->normalizeWpSlug($rawSlug) ?: (Str::slug($post->post_title).'-'.$post->ID);
                $slug = mb_substr($slug, 0, 180);
                $excerpt = trim((string) ($post->post_excerpt ?: ''));
                if ($excerpt === '') {
                    $excerpt = Str::limit(trim(strip_tags((string) $post->post_content)), 400, '');
                }
                $author = $authors->get($post->post_author)?->display_name;
                $image = $this->featuredImageUrl($meta);

                if ($dryRun) {
                    CmsPost::withTrashed()
                        ->where('slug', $slug)
                        ->orWhere('slug', $rawSlug)
                        ->exists() ? $updated++ : $created++;
                    $onProgress && $onProgress();

                    continue;
                }

                $cmsPost = CmsPost::withTrashed()
                    ->where(function ($q) use ($slug, $rawSlug) {
                        $q->where('slug', $slug);
                        if ($rawSlug !== '' && $rawSlug !== $slug) {
                            $q->orWhere('slug', $rawSlug);
                        }
                    })
                    ->first();

                $payload = [
                    'slug' => $slug,
                    'title' => mb_substr((string) $post->post_title, 0, 191),
                    'excerpt' => $excerpt !== '' ? mb_substr($excerpt, 0, 500) : null,
                    'body' => $post->post_content ?: null,
                    'featured_image' => $image,
                    'author' => $author ? mb_substr($author, 0, 191) : null,
                    'is_published' => true,
                    'status' => CmsPost::STATUS_PUBLISHED,
                    'published_at' => $post->post_date ? Carbon::parse($post->post_date) : now(),
                ];

                if ($cmsPost) {
                    if ($cmsPost->trashed()) {
                        $cmsPost->restore();
                    }
                    $cmsPost->fill($payload)->save();
                    $updated++;
                } else {
                    $cmsPost = CmsPost::query()->create($payload);
                    $created++;
                }

                $cmsPost->reading_time_minutes = $cmsPost->estimateReadingTime();
                $cmsPost->save();

                $this->syncModelCategories($cmsPost, (int) $post->ID, 'category', Category::TYPE_POST);

                $this->ensureRedirect('/'.$slug, '/blog/'.$slug);
                if ($rawSlug !== '' && $rawSlug !== $slug) {
                    $this->ensureRedirect('/'.$rawSlug, '/blog/'.$slug);
                }
                $this->ensureRedirect('/?p='.$post->ID, '/blog/'.$slug);

                $onProgress && $onProgress();
            }
        }

        return compact('created', 'updated');
    }

    /**
     * @param  (callable(): void)|null  $onProgress  called once per WP paid order
     */
    public function migrateOrders(bool $dryRun = false, ?callable $onProgress = null): array
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
                $onProgress && $onProgress();

                continue;
            }

            if ($dryRun) {
                if (! Order::query()->where('wp_id', $wpOrder->ID)->exists()) {
                    $created++;
                }
                $onProgress && $onProgress();

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

            $onProgress && $onProgress();
        }

        return compact('created', 'enrolled');
    }

    public function audit(): array
    {
        return [
            'wp_users' => $this->wp()->table('users')->count(),
            'local_users_with_wp_id' => User::query()->whereNotNull('wp_id')->count(),
            'wp_product_cats' => $this->wp()->table('term_taxonomy')->where('taxonomy', 'product_cat')->count(),
            'local_product_categories' => Category::query()->where('type', Category::TYPE_PRODUCT)->count(),
            'wp_post_cats' => $this->wp()->table('term_taxonomy')->where('taxonomy', 'category')->count(),
            'local_post_categories' => Category::query()->where('type', Category::TYPE_POST)->count(),
            'wp_products' => $this->wp()->table('posts')->where('post_type', 'product')->whereIn('post_status', ['publish', 'private'])->count(),
            'local_courses' => Course::query()->count(),
            'local_course_category_links' => DB::table('categoryables')
                ->where('categoryable_type', ShopProduct::class)
                ->count(),
            'wp_posts' => $this->wp()->table('posts')->where('post_type', 'post')->where('post_status', 'publish')->count(),
            'local_posts' => CmsPost::query()->count(),
            'local_post_category_links' => DB::table('categoryables')
                ->where('categoryable_type', CmsPost::class)
                ->count(),
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
        if (strlen($from) > 191 || strlen($to) > 191) {
            return;
        }

        CmsRedirect::query()->updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => 301, 'is_active' => true]
        );
    }

    private function isSkippedCategorySlug(string $slug): bool
    {
        $normalized = mb_strtolower(trim($slug));

        return in_array($normalized, [
            'uncategorized',
            'uncategorised',
            'دسته-بندی-نشده',
            'دسته‌بندی-نشده',
            'دسته بندی نشده ها',
        ], true) || str_contains($normalized, 'دسته‌بندی نشده') || str_contains($normalized, 'دسته بندی نشده');
    }

    /**
     * @param  Model&object{syncCategories: callable}  $model
     */
    private function syncModelCategories(Model $model, int $wpPostId, string $taxonomy, string $categoryType): void
    {
        $rows = $this->wp()->table('term_relationships as tr')
            ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->join('terms as t', 't.term_id', '=', 'tt.term_id')
            ->where('tr.object_id', $wpPostId)
            ->where('tt.taxonomy', $taxonomy)
            ->orderBy('tr.term_order')
            ->get(['t.slug', 't.name']);

        $ids = [];
        foreach ($rows as $row) {
            $slug = $this->normalizeWpSlug((string) $row->slug);
            if ($slug === '' || $this->isSkippedCategorySlug($slug)) {
                continue;
            }

            $category = Category::query()
                ->where('type', $categoryType)
                ->where(function ($q) use ($slug) {
                    $q->where('slug', $slug)->orWhere('full_slug', $slug);
                })
                ->first();

            if (! $category) {
                $category = Category::query()
                    ->where('type', $categoryType)
                    ->where('name', trim((string) $row->name))
                    ->first();
            }

            if ($category) {
                $ids[] = $category->id;
            }
        }

        $model->syncCategories($ids);
    }

    /** @param  array<string, mixed>  $meta */
    private function featuredImageUrl(array $meta): ?string
    {
        $thumbId = (int) ($meta['_thumbnail_id'] ?? 0);
        if ($thumbId <= 0) {
            return null;
        }

        $attachment = $this->wp()->table('posts')
            ->where('ID', $thumbId)
            ->where('post_type', 'attachment')
            ->first(['guid']);

        $url = trim((string) ($attachment->guid ?? ''));
        if ($url === '' || strlen($url) > 191) {
            return null;
        }

        return $url;
    }

    /**
     * WordPress sometimes stores percent-encoded Persian in post_name;
     * Laravel route params arrive decoded, so slugs must match that form.
     */
    private function normalizeWpSlug(string $slug): string
    {
        $slug = trim($slug);

        if ($slug === '') {
            return '';
        }

        if (str_contains($slug, '%')) {
            $decoded = rawurldecode($slug);
            if ($decoded !== '' && $decoded !== $slug) {
                return $decoded;
            }
        }

        return $slug;
    }

    /**
     * SpotPlayer course IDs in WP can be comma-separated (bundles).
     * Local column is a single varchar; keep the first valid ID for now.
     */
    private function normalizeSpotplayerCourseId(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $first = collect(explode(',', (string) $raw))
            ->map(fn (string $id) => trim($id))
            ->filter()
            ->first();

        if (! is_string($first) || $first === '') {
            return null;
        }

        return mb_substr($first, 0, 100);
    }
}
