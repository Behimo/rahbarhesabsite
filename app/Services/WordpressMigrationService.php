<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsRedirect;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\ShopProduct;
use App\Models\Tag;
use App\Models\User;
use App\Support\AccessCatalog;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            ->whereIn('taxonomy', ['product_cat', 'category', 'post_tag', 'product_tag'])
            ->count();
    }

    public function countTermLinkTargets(): int
    {
        return $this->countProducts() + $this->countPosts();
    }

    public function countPosts(): int
    {
        return (int) $this->wp()->table('posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();
    }

    public function countPages(): int
    {
        return (int) $this->wp()->table('posts')
            ->where('post_type', 'page')
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

            foreach ($this->wpTermsForTaxonomy($taxonomy) as $term) {
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

        $tagStats = $this->migrateTags($dryRun, $onProgress);

        return [
            'created' => $created + $tagStats['created'],
            'updated' => $updated + $tagStats['updated'],
            'skipped' => $skipped + $tagStats['skipped'],
            'tags_created' => $tagStats['created'],
            'tags_updated' => $tagStats['updated'],
        ];
    }

    /**
     * Import WP post_tag / product_tag into local tags table.
     *
     * @param  (callable(): void)|null  $onProgress
     * @return array{created: int, updated: int, skipped: int}
     */
    public function migrateTags(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (['post_tag', 'product_tag'] as $taxonomy) {
            foreach ($this->wpTermsForTaxonomy($taxonomy) as $term) {
                $slug = $this->normalizeWpSlug((string) $term->slug);
                $name = trim((string) $term->name);

                if ($slug === '' || $name === '') {
                    $skipped++;
                    $onProgress && $onProgress();

                    continue;
                }

                $slug = mb_substr($slug, 0, 191);

                if ($dryRun) {
                    Tag::query()->where('slug', $slug)->exists() ? $updated++ : $created++;
                    $onProgress && $onProgress();

                    continue;
                }

                $tag = Tag::query()->where('slug', $slug)->first();
                if ($tag) {
                    $tag->fill(['name' => mb_substr($name, 0, 191)])->save();
                    $updated++;
                } else {
                    Tag::query()->create([
                        'name' => mb_substr($name, 0, 191),
                        'slug' => $slug,
                    ]);
                    $created++;
                }

                $onProgress && $onProgress();
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * Rebuild category/tag pivots for already-migrated courses and posts.
     *
     * @param  (callable(): void)|null  $onProgress
     * @return array{products_linked: int, posts_linked: int, product_category_links: int, post_category_links: int, post_tag_links: int, missing_products: int, missing_posts: int}
     */
    public function migrateTermLinks(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $productsLinked = 0;
        $postsLinked = 0;
        $productCategoryLinks = 0;
        $postCategoryLinks = 0;
        $postTagLinks = 0;
        $missingProducts = 0;
        $missingPosts = 0;

        $products = $this->wp()->table('posts')
            ->where('post_type', 'product')
            ->whereIn('post_status', ['publish', 'private'])
            ->orderBy('ID')
            ->get(['ID', 'post_name', 'post_title']);

        foreach ($products as $post) {
            $rawSlug = (string) ($post->post_name ?: '');
            $slug = $this->normalizeWpSlug($rawSlug) ?: (Str::slug((string) $post->post_title).'-'.$post->ID);
            $product = ShopProduct::query()
                ->where(function ($q) use ($slug, $rawSlug) {
                    $q->where('slug', $slug);
                    if ($rawSlug !== '' && $rawSlug !== $slug) {
                        $q->orWhere('slug', $rawSlug);
                    }
                })
                ->first();

            if (! $product) {
                $missingProducts++;
                $onProgress && $onProgress();

                continue;
            }

            $catIds = $this->resolveCategoryIdsForWpObject((int) $post->ID, 'product_cat', Category::TYPE_PRODUCT);
            if (! $dryRun) {
                $product->syncCategories($catIds);
            }
            $productsLinked++;
            $productCategoryLinks += count($catIds);
            $onProgress && $onProgress();
        }

        $posts = $this->wp()->table('posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get(['ID', 'post_name', 'post_title']);

        foreach ($posts as $post) {
            $rawSlug = (string) ($post->post_name ?: '');
            $slug = $this->normalizeWpSlug($rawSlug) ?: (Str::slug((string) $post->post_title).'-'.$post->ID);
            $slug = mb_substr($slug, 0, 180);

            $cmsPost = CmsPost::withTrashed()
                ->where(function ($q) use ($slug, $rawSlug) {
                    $q->where('slug', $slug);
                    if ($rawSlug !== '' && $rawSlug !== $slug) {
                        $q->orWhere('slug', $rawSlug);
                    }
                })
                ->first();

            if (! $cmsPost) {
                $missingPosts++;
                $onProgress && $onProgress();

                continue;
            }

            $catIds = $this->resolveCategoryIdsForWpObject((int) $post->ID, 'category', Category::TYPE_POST);
            $tagIds = $this->resolveTagIdsForWpObject((int) $post->ID, 'post_tag');

            if (! $dryRun) {
                if ($cmsPost->trashed()) {
                    $cmsPost->restore();
                }
                $cmsPost->syncCategories($catIds);
                $cmsPost->syncTags($tagIds);
            }

            $postsLinked++;
            $postCategoryLinks += count($catIds);
            $postTagLinks += count($tagIds);
            $onProgress && $onProgress();
        }

        return [
            'products_linked' => $productsLinked,
            'posts_linked' => $postsLinked,
            'product_category_links' => $productCategoryLinks,
            'post_category_links' => $postCategoryLinks,
            'post_tag_links' => $postTagLinks,
            'missing_products' => $missingProducts,
            'missing_posts' => $missingPosts,
        ];
    }

    /**
     * Fast path: bulk read + bulk upsert (no per-user bcrypt / Eloquent / role sync).
     *
     * @param  (callable(): void)|null  $onProgress  called once per WP user row
     * @return array{created: int, updated: int, pending_activation: int, duplicate_mobile: int}
     */
    public function migrateUsers(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $created = 0;
        $updated = 0;
        $pendingActivation = 0;
        $duplicateMobile = 0;

        $orderCounts = $this->paidOrderCountsByCustomer();
        $candidates = [];

        foreach ($this->wp()->table('users')->orderBy('ID')->lazyById(500, 'ID')->chunk(500) as $batch) {
            $userMeta = $this->userMetaForIds($batch->pluck('ID')->all());
            foreach ($batch as $row) {
                $meta = $userMeta[$row->ID] ?? [];
                $mobile = $this->resolveMobile($meta, (string) $row->user_login);
                $candidates[] = [
                    'row' => $row,
                    'meta' => $meta,
                    'mobile' => $mobile,
                    'orders' => (int) ($orderCounts[(int) $row->ID] ?? 0),
                ];
            }
        }

        $winnersByMobile = $this->mobileWinners($candidates);

        $existingByWpId = DB::table('users')
            ->whereNotNull('wp_id')
            ->get(['id', 'wp_id', 'email', 'deleted_at'])
            ->keyBy(fn ($row) => (int) $row->wp_id);

        $usedEmails = [];
        foreach (DB::table('users')->whereNotNull('email')->get(['email', 'wp_id']) as $row) {
            $email = mb_strtolower((string) $row->email);
            $usedEmails[$email] = (int) ($row->wp_id ?? 0);
        }

        $now = now()->toDateTimeString();
        $rows = [];
        $winnerReleases = [];

        foreach ($candidates as $candidate) {
            $row = $candidate['row'];
            $meta = $candidate['meta'];
            $mobile = $candidate['mobile'];
            $wpId = (int) $row->ID;
            $isWinner = $mobile !== null && ($winnersByMobile[$mobile] ?? null) === $wpId;
            $isDuplicate = $mobile !== null && ! $isWinner;

            if ($isDuplicate) {
                $duplicateMobile++;
            }

            $status = $isWinner ? User::STATUS_ACTIVE : User::STATUS_PENDING_ACTIVATION;
            if ($status === User::STATUS_PENDING_ACTIVATION) {
                $pendingActivation++;
            }

            if ($isWinner && $mobile) {
                $winnerReleases[$mobile] = $wpId;
            }

            $name = trim(($meta['first_name'] ?? '').' '.($meta['last_name'] ?? ''))
                ?: ($row->display_name ?: $row->user_login);

            $email = $this->claimUniqueEmail($row->user_email ?: null, $wpId, $usedEmails);
            $password = (string) $row->user_pass;
            if ($password === '') {
                $password = '!';
            }

            $existing = $existingByWpId->get($wpId);
            if ($existing) {
                $updated++;
            } else {
                $created++;
            }

            $rows[] = [
                'wp_id' => $wpId,
                'name' => mb_substr((string) $name, 0, 191),
                'first_name' => isset($meta['first_name']) ? mb_substr((string) $meta['first_name'], 0, 100) : null,
                'last_name' => isset($meta['last_name']) ? mb_substr((string) $meta['last_name'], 0, 100) : null,
                'email' => $email,
                'phone' => $isWinner ? $mobile : null,
                'mobile' => $isWinner ? $mobile : null,
                'claimed_mobile' => $isDuplicate ? $mobile : ($isWinner ? null : $this->rawClaimedMobile($meta, (string) $row->user_login)),
                'status' => $status,
                'password' => $password,
                'is_wp_password' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        }

        if ($dryRun) {
            foreach ($rows as $_) {
                $onProgress && $onProgress();
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'pending_activation' => $pendingActivation,
                'duplicate_mobile' => $duplicateMobile,
            ];
        }

        $this->bulkReleaseWinnerMobiles($winnerReleases);

        $updateColumns = [
            'name', 'first_name', 'last_name', 'email', 'phone', 'mobile', 'claimed_mobile',
            'status', 'password', 'is_wp_password', 'updated_at', 'deleted_at',
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('users')->upsert($chunk, ['wp_id'], $updateColumns);
            if ($onProgress) {
                for ($i = 0, $n = count($chunk); $i < $n; $i++) {
                    $onProgress();
                }
            }
        }

        $wpIds = array_column($rows, 'wp_id');
        $localIds = [];
        foreach (array_chunk($wpIds, 1000) as $chunk) {
            foreach (DB::table('users')->whereIn('wp_id', $chunk)->pluck('id') as $id) {
                $localIds[] = (int) $id;
            }
        }
        $this->bulkEnsureSiteRoles($localIds);

        return [
            'created' => $created,
            'updated' => $updated,
            'pending_activation' => $pendingActivation,
            'duplicate_mobile' => $duplicateMobile,
        ];
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

                $this->applyRankMathMeta($product, $meta);
                $product->wp_post_id = (int) $post->ID;
                $product->save();

                // URL parity: /product/{slug} stays canonical — only map legacy ?p= IDs.
                $this->ensureRedirect('/?p='.$post->ID, '/product/'.$slug.'/');

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
                $this->syncModelTags($cmsPost, (int) $post->ID, 'post_tag');

                $this->applyRankMathMeta($cmsPost, $meta);
                $cmsPost->wp_post_id = (int) $post->ID;
                if (! empty($meta['_thumbnail_id'])) {
                    $cmsPost->featured_image_alt = $this->attachmentAlt((int) $meta['_thumbnail_id']) ?: $cmsPost->featured_image_alt;
                }
                $cmsPost->save();

                $cmsPost->load('categories');
                $permalink = app(\App\Services\PermalinkService::class)->post($cmsPost);
                $this->ensureRedirect('/?p='.$post->ID, $permalink);

                $onProgress && $onProgress();
            }
        }

        return compact('created', 'updated');
    }

    /**
     * Import WordPress pages (including Elementor) into CmsPage with HTML + stylesheets.
     *
     * @param  (callable(): void)|null  $onProgress
     * @param  array{source?: string, include_system?: bool, download_css?: bool}  $options
     * @return array{created: int, updated: int, skipped: int, rendered: int, content_fallback: int}
     */
    public function migratePages(bool $dryRun = false, ?callable $onProgress = null, array $options = []): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $rendered = 0;
        $contentFallback = 0;

        $source = strtolower((string) ($options['source'] ?? 'rendered'));
        $includeSystem = (bool) ($options['include_system'] ?? false);
        $downloadCss = (bool) ($options['download_css'] ?? false);
        $urlRewriter = app(ContentUrlRewriter::class);

        $pages = $this->wp()->table('posts')
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->lazyById(100, 'ID');

        foreach ($pages->chunk(50) as $batch) {
            $ids = $batch->pluck('ID')->map(fn ($id) => (int) $id)->all();
            $postMeta = $this->postMetaForIds($ids);

            foreach ($batch as $post) {
                $rawSlug = (string) ($post->post_name ?: '');
                $slug = $this->normalizeWpSlug($rawSlug) ?: (Str::slug((string) $post->post_title).'-'.$post->ID);
                $slug = mb_substr($slug, 0, 180);

                if (! $includeSystem && $this->shouldSkipWpPage($slug, $rawSlug)) {
                    $skipped++;
                    $onProgress && $onProgress();

                    continue;
                }

                $meta = $postMeta[$post->ID] ?? [];
                $importSource = 'post_content';
                $bodyHtml = (string) ($post->post_content ?: '');
                $stylesheets = [];

                if ($source === 'rendered') {
                    $fetched = $this->fetchRenderedWpPage($slug, (int) $post->ID);
                    if ($fetched !== null) {
                        $bodyHtml = $fetched['html'];
                        $stylesheets = $fetched['stylesheets'];
                        $importSource = 'wp-rendered';
                        $rendered++;
                    } else {
                        $contentFallback++;
                    }
                }

                $bodyHtml = $this->stripDangerousHtml($bodyHtml);
                $bodyHtml = wp_shortcodes($bodyHtml);
                $bodyHtml = $urlRewriter->rewrite($bodyHtml);

                if ($downloadCss && $stylesheets !== [] && ! $dryRun) {
                    $stylesheets = $this->localizeStylesheets($stylesheets, (int) $post->ID);
                }

                if ($dryRun) {
                    CmsPage::withTrashed()
                        ->where('wp_post_id', (int) $post->ID)
                        ->orWhere('slug', $slug)
                        ->exists() ? $updated++ : $created++;
                    $onProgress && $onProgress();

                    continue;
                }

                $cmsPage = CmsPage::withTrashed()
                    ->where('wp_post_id', (int) $post->ID)
                    ->first();

                if (! $cmsPage) {
                    $cmsPage = CmsPage::withTrashed()
                        ->where(function ($q) use ($slug, $rawSlug) {
                            $q->where('slug', $slug);
                            $decodedRaw = $this->normalizeWpSlug($rawSlug);
                            if ($decodedRaw !== '' && $decodedRaw !== $slug) {
                                $q->orWhere('slug', $decodedRaw);
                            }
                            if ($rawSlug !== '' && $rawSlug !== $slug) {
                                $q->orWhere('slug', $rawSlug);
                            }
                        })
                        ->first();
                }

                $payload = [
                    'slug' => $slug,
                    'title' => mb_substr((string) $post->post_title, 0, 191),
                    'template' => 'wp-html',
                    'builder_enabled' => false,
                    'content' => [
                        'body_html' => $bodyHtml,
                        'stylesheet_urls' => $stylesheets,
                        'import_source' => $importSource,
                    ],
                    'is_published' => true,
                    'status' => 'published',
                    'published_at' => $post->post_date ? Carbon::parse($post->post_date) : now(),
                    'is_system' => false,
                    'show_in_nav' => false,
                    'wp_post_id' => (int) $post->ID,
                ];

                if ($cmsPage) {
                    if ($cmsPage->trashed()) {
                        $cmsPage->restore();
                    }
                    // Keep Laravel-owned system pages from being overwritten into catch-all templates.
                    if ($cmsPage->is_system) {
                        $skipped++;
                        $onProgress && $onProgress();

                        continue;
                    }
                    $cmsPage->fill($payload)->save();
                    $updated++;
                } else {
                    $cmsPage = CmsPage::query()->create($payload);
                    $created++;
                }

                $this->applyRankMathMeta($cmsPage, $meta);
                $cmsPage->wp_post_id = (int) $post->ID;
                $cmsPage->save();

                $permalink = app(PermalinkService::class)->page($cmsPage);
                $this->ensureRedirect('/?p='.$post->ID, $permalink);
                // Staging Laravel used /p/{slug}; keep a 301 to the final page URL.
                $this->ensureRedirect('/p/'.$slug, $permalink);
                $this->ensureRedirect('/p/'.$slug.'/', $permalink);
                if ($rawSlug !== '' && $this->normalizeWpSlug($rawSlug) !== $slug) {
                    $this->ensureRedirect('/'.$rawSlug.'/', $permalink);
                }

                $onProgress && $onProgress();
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'rendered' => $rendered,
            'content_fallback' => $contentFallback,
        ];
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

    public function audit(): array
    {
        return [
            'wp_users' => $this->wp()->table('users')->count(),
            'local_users_with_wp_id' => User::query()->whereNotNull('wp_id')->count(),
            'local_pending_activation' => User::query()->where('status', User::STATUS_PENDING_ACTIVATION)->count(),
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
            'wp_pages' => $this->wp()->table('posts')->where('post_type', 'page')->where('post_status', 'publish')->count(),
            'local_pages_with_wp_post_id' => CmsPage::query()->whereNotNull('wp_post_id')->count(),
            'local_wp_html_pages' => CmsPage::query()->where('template', 'wp-html')->count(),
            'wp_post_tags' => $this->wp()->table('term_taxonomy')->where('taxonomy', 'post_tag')->count(),
            'local_tags' => Tag::query()->count(),
            'local_taggables' => DB::table('taggables')->count(),
            'wp_paid_orders' => $this->wp()->table('posts')->where('post_type', 'shop_order')->whereIn('post_status', ['wc-completed', 'wc-processing'])->count(),
            'local_wp_orders' => Order::query()->whereNotNull('wp_id')->count(),
            'local_orders_needs_review' => Order::query()->where('needs_review', true)->count(),
            'local_orders_without_user' => Order::query()->whereNull('user_id')->count(),
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

    /**
     * @return array<int, int> wp_user_id => paid order count
     */
    private function paidOrderCountsByCustomer(): array
    {
        $orderIds = $this->wp()->table('posts')
            ->where('post_type', 'shop_order')
            ->whereIn('post_status', ['wc-completed', 'wc-processing'])
            ->pluck('ID');

        if ($orderIds->isEmpty()) {
            return [];
        }

        $counts = [];
        foreach ($orderIds->chunk(500) as $chunk) {
            $rows = $this->wp()->table('postmeta')
                ->whereIn('post_id', $chunk->all())
                ->where('meta_key', '_customer_user')
                ->get(['meta_value']);

            foreach ($rows as $row) {
                $uid = (int) $row->meta_value;
                if ($uid <= 0) {
                    continue;
                }
                $counts[$uid] = ($counts[$uid] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $usedEmails  lowercase email => owning wp_id (0 = local-only)
     */
    private function claimUniqueEmail(?string $email, int $wpId, array &$usedEmails): ?string
    {
        $email = $email !== null ? trim($email) : null;
        if ($email === null || $email === '') {
            return null;
        }

        $email = mb_substr($email, 0, 255);
        $key = mb_strtolower($email);
        $owner = $usedEmails[$key] ?? null;

        if ($owner !== null && $owner !== $wpId) {
            return null;
        }

        $usedEmails[$key] = $wpId;

        return $email;
    }

    /**
     * @param  array<string, int>  $winnerReleases  mobile => winner wp_id
     */
    private function bulkReleaseWinnerMobiles(array $winnerReleases): void
    {
        foreach ($winnerReleases as $mobile => $keepWpId) {
            $ids = DB::table('users')
                ->where(function ($q) use ($mobile) {
                    $q->where('mobile', $mobile)->orWhere('phone', $mobile);
                })
                ->where(function ($q) use ($keepWpId) {
                    $q->whereNull('wp_id')->orWhere('wp_id', '!=', $keepWpId);
                })
                ->pluck('id');

            if ($ids->isEmpty()) {
                continue;
            }

            DB::table('users')
                ->whereIn('id', $ids)
                ->whereNull('claimed_mobile')
                ->update(['claimed_mobile' => $mobile]);

            DB::table('users')
                ->whereIn('id', $ids)
                ->where('status', User::STATUS_ACTIVE)
                ->update(['status' => User::STATUS_PENDING_ACTIVATION]);

            DB::table('users')
                ->whereIn('id', $ids)
                ->update([
                    'mobile' => null,
                    'phone' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @param  list<int|string>  $userIds
     */
    private function bulkEnsureSiteRoles(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $role = Role::query()
            ->where('name', AccessCatalog::ROLE_USER)
            ->where('guard_name', AccessCatalog::guard())
            ->first();

        if (! $role) {
            return;
        }

        $modelType = (new User)->getMorphClass();
        $already = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', $modelType)
            ->whereIn('model_id', $userIds)
            ->pluck('model_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $alreadySet = array_fill_keys($already, true);

        $inserts = [];
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if (isset($alreadySet[$userId])) {
                continue;
            }
            // Skip users that already have any role.
            $inserts[$userId] = [
                'role_id' => $role->id,
                'model_type' => $modelType,
                'model_id' => $userId,
            ];
        }

        if ($inserts === []) {
            return;
        }

        $withAnyRole = DB::table('model_has_roles')
            ->where('model_type', $modelType)
            ->whereIn('model_id', array_keys($inserts))
            ->pluck('model_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        foreach ($withAnyRole as $id) {
            unset($inserts[$id]);
        }

        foreach (array_chunk(array_values($inserts), 500) as $chunk) {
            DB::table('model_has_roles')->insert($chunk);
        }
    }

    /**
     * @param  list<array{row: object, meta: array<string, mixed>, mobile: ?string, orders: int}>  $candidates
     * @return array<string, int> mobile => winning wp_id
     */
    private function mobileWinners(array $candidates): array
    {
        $byMobile = [];
        foreach ($candidates as $candidate) {
            $mobile = $candidate['mobile'];
            if ($mobile === null) {
                continue;
            }
            $byMobile[$mobile][] = $candidate;
        }

        $winners = [];
        foreach ($byMobile as $mobile => $group) {
            usort($group, function (array $a, array $b): int {
                if ($a['orders'] !== $b['orders']) {
                    return $b['orders'] <=> $a['orders'];
                }

                return (int) $a['row']->ID <=> (int) $b['row']->ID;
            });
            $winners[$mobile] = (int) $group[0]['row']->ID;
        }

        return $winners;
    }

    private function releaseMobileFromOtherUsers(string $mobile, int $keepWpId): void
    {
        User::withTrashed()
            ->where(function ($q) use ($mobile) {
                $q->where('mobile', $mobile)->orWhere('phone', $mobile);
            })
            ->where(function ($q) use ($keepWpId) {
                $q->whereNull('wp_id')->orWhere('wp_id', '!=', $keepWpId);
            })
            ->get()
            ->each(function (User $user) use ($mobile): void {
                $user->forceFill([
                    'mobile' => null,
                    'phone' => null,
                    'claimed_mobile' => $user->claimed_mobile ?: $mobile,
                    'status' => $user->status === User::STATUS_ACTIVE
                        ? User::STATUS_PENDING_ACTIVATION
                        : $user->status,
                ])->save();
            });
    }

    private function uniqueEmailOrNull(?string $email, int $wpId): ?string
    {
        $email = $email !== null ? trim($email) : null;
        if ($email === null || $email === '') {
            return null;
        }

        $taken = User::withTrashed()
            ->where('email', $email)
            ->where(function ($q) use ($wpId) {
                $q->whereNull('wp_id')->orWhere('wp_id', '!=', $wpId);
            })
            ->exists();

        return $taken ? null : mb_substr($email, 0, 255);
    }

    /** @param  array<string, mixed>  $meta */
    private function rawClaimedMobile(array $meta, string $fallback): ?string
    {
        foreach (['loginx_phone', 'digits_phone', 'billing_phone', 'phone'] as $key) {
            $raw = trim((string) ($meta[$key] ?? ''));
            if ($raw !== '') {
                return mb_substr($raw, 0, 15);
            }
        }

        $fallback = trim($fallback);
        if ($fallback !== '' && preg_match('/\d{8,}/', $fallback)) {
            return mb_substr($fallback, 0, 15);
        }

        return null;
    }

    /** @param  array<string, mixed>  $meta */
    private function findOrCreateUserForBillingPhone(string $mobile, array $meta, bool $dryRun): ?User
    {
        $existing = User::query()
            ->where(function ($q) use ($mobile) {
                $q->where('mobile', $mobile)->orWhere('phone', $mobile);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        if ($dryRun) {
            return new User(['mobile' => $mobile, 'phone' => $mobile, 'status' => User::STATUS_ACTIVE]);
        }

        $first = trim((string) ($meta['_billing_first_name'] ?? ''));
        $last = trim((string) ($meta['_billing_last_name'] ?? ''));
        $name = trim($first.' '.$last);
        if ($name === '') {
            $name = 'کاربر '.substr($mobile, -4);
        }

        $now = now()->toDateTimeString();
        $email = $this->uniqueEmailOrNull(
            isset($meta['_billing_email']) ? (string) $meta['_billing_email'] : null,
            0
        );

        $id = DB::table('users')->insertGetId([
            'name' => mb_substr($name, 0, 191),
            'first_name' => $first !== '' ? mb_substr($first, 0, 100) : null,
            'last_name' => $last !== '' ? mb_substr($last, 0, 100) : null,
            'email' => $email,
            'phone' => $mobile,
            'mobile' => $mobile,
            'password' => '!',
            'is_wp_password' => false,
            'status' => User::STATUS_ACTIVE,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->bulkEnsureSiteRoles([$id]);

        return User::query()->findOrFail($id);
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
            ['from_path' => $from, 'from_query' => ''],
            [
                'to_path' => $to,
                'status_code' => 301,
                'is_active' => true,
                'source' => 'wp-migrate',
                'match_query' => false,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function applyRankMathMeta(Model $model, array $meta): void
    {
        $title = $this->metaString($meta, ['rank_math_title', 'rank_math_facebook_title']);
        $description = $this->metaString($meta, ['rank_math_description', 'rank_math_facebook_description']);
        $robots = $this->rankMathRobots($meta);
        $canonical = $this->metaString($meta, ['rank_math_canonical_url']);
        $ogImage = $this->metaString($meta, ['rank_math_facebook_image', 'rank_math_twitter_image']);

        if ($title) {
            $model->meta_title = mb_substr($title, 0, 191);
        }
        if ($description) {
            $model->meta_description = mb_substr($description, 0, 500);
        }
        if ($robots && $model->isFillable('robots')) {
            $model->robots = $robots;
        }
        if ($canonical && $model->isFillable('canonical_url')) {
            $model->canonical_url = mb_substr($canonical, 0, 500);
        }
        if ($ogImage && $model->isFillable('og_image')) {
            $model->og_image = mb_substr($ogImage, 0, 191);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<string>  $keys
     */
    private function metaString(array $meta, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($meta[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function rankMathRobots(array $meta): ?string
    {
        $raw = $meta['rank_math_robots'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        $parts = is_array($raw) ? $raw : (array) @unserialize((string) $raw);
        if (! is_array($parts) || $parts === []) {
            $parts = array_filter(array_map('trim', explode(',', (string) $raw)));
        }

        $parts = array_values(array_filter(array_map(fn ($p) => mb_strtolower(trim((string) $p)), $parts)));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function attachmentAlt(int $attachmentId): ?string
    {
        if ($attachmentId <= 0) {
            return null;
        }

        $alt = $this->wp()->table('postmeta')
            ->where('post_id', $attachmentId)
            ->where('meta_key', '_wp_attachment_image_alt')
            ->value('meta_value');

        $alt = trim((string) $alt);

        return $alt !== '' ? mb_substr($alt, 0, 191) : null;
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
        $model->syncCategories($this->resolveCategoryIdsForWpObject($wpPostId, $taxonomy, $categoryType));
    }

    /**
     * @param  Model&object{syncTags: callable}  $model
     */
    private function syncModelTags(Model $model, int $wpPostId, string $taxonomy): void
    {
        $model->syncTags($this->resolveTagIdsForWpObject($wpPostId, $taxonomy));
    }

    /**
     * @return list<int>
     */
    private function resolveCategoryIdsForWpObject(int $wpPostId, string $taxonomy, string $categoryType): array
    {
        $ids = [];
        foreach ($this->wpTermsForObject($wpPostId, $taxonomy) as $row) {
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

        return array_values(array_unique($ids));
    }

    /**
     * @return list<int>
     */
    private function resolveTagIdsForWpObject(int $wpPostId, string $taxonomy): array
    {
        $ids = [];
        foreach ($this->wpTermsForObject($wpPostId, $taxonomy) as $row) {
            $slug = $this->normalizeWpSlug((string) $row->slug);
            if ($slug === '') {
                continue;
            }

            $tag = Tag::query()->where('slug', mb_substr($slug, 0, 191))->first();
            if (! $tag) {
                $tag = Tag::query()->where('name', trim((string) $row->name))->first();
            }

            if ($tag) {
                $ids[] = $tag->id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Raw SQL avoids Laravel WP table-prefix breaking aliases / qualified columns.
     *
     * @return Collection<int, object{term_id: int|string, name: string, slug: string, parent: int|string, description: ?string}>
     */
    private function wpTermsForTaxonomy(string $taxonomy): Collection
    {
        $p = $this->wp()->getTablePrefix();

        return collect($this->wp()->select(
            "SELECT t.term_id, t.name, t.slug, tt.parent, tt.description
             FROM {$p}term_taxonomy tt
             INNER JOIN {$p}terms t ON t.term_id = tt.term_id
             WHERE tt.taxonomy = ?
             ORDER BY tt.parent ASC, t.term_id ASC",
            [$taxonomy]
        ));
    }

    /**
     * @return Collection<int, object{slug: string, name: string}>
     */
    private function wpTermsForObject(int $objectId, string $taxonomy): Collection
    {
        $p = $this->wp()->getTablePrefix();

        return collect($this->wp()->select(
            "SELECT t.slug, t.name
             FROM {$p}term_relationships tr
             INNER JOIN {$p}term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             INNER JOIN {$p}terms t ON t.term_id = tt.term_id
             WHERE tr.object_id = ? AND tt.taxonomy = ?
             ORDER BY tr.term_order ASC, t.term_id ASC",
            [$objectId, $taxonomy]
        ));
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

    private function shouldSkipWpPage(string $slug, string $rawSlug = ''): bool
    {
        $skip = array_map('mb_strtolower', config('cms.wordpress.skip_page_slugs', []));
        $candidates = array_filter([
            mb_strtolower($slug),
            mb_strtolower($this->normalizeWpSlug($rawSlug)),
            mb_strtolower($rawSlug),
        ]);

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $skip, true)) {
                return true;
            }
        }

        // Reserved route prefixes must not become catch-all CmsPage rows.
        if (app(PermalinkService::class)->isReservedPrefix($slug)) {
            return true;
        }

        return false;
    }

    private function wpSiteUrl(): string
    {
        $configured = rtrim((string) config('cms.wordpress.site_url', ''), '/');
        if ($configured !== '') {
            return $configured;
        }

        try {
            $url = (string) ($this->wp()->table('options')->where('option_name', 'home')->value('option_value')
                ?: $this->wp()->table('options')->where('option_name', 'siteurl')->value('option_value')
                ?: '');
            $url = rtrim($url, '/');
            if ($url !== '') {
                return $url;
            }
        } catch (\Throwable) {
            // options table may be missing in tests
        }

        return 'https://rahbarhesab.com';
    }

    /**
     * @return array{html: string, stylesheets: list<string>}|null
     */
    private function fetchRenderedWpPage(string $slug, int $wpId): ?array
    {
        $base = $this->wpSiteUrl();
        $candidates = array_values(array_unique(array_filter([
            $base.'/'.rawurlencode($slug).'/',
            $base.'/'.$slug.'/',
            $base.'/?page_id='.$wpId,
            $base.'/?p='.$wpId,
        ])));

        foreach ($candidates as $url) {
            try {
                $response = Http::timeout(25)
                    ->withHeaders(['User-Agent' => 'RahbarHesabWpMigrate/1.0'])
                    ->get($url);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $html = (string) $response->body();
            $root = $this->extractElementorRoot($html);
            if ($root === null || trim($root) === '') {
                continue;
            }

            return [
                'html' => $root,
                'stylesheets' => $this->collectElementorStylesheets($html, $wpId, $base),
            ];
        }

        return null;
    }

    private function extractElementorRoot(string $html): ?string
    {
        if (! preg_match('/<div[^>]*data-elementor-type=["\']wp-page["\'][^>]*>/i', $html, $match, PREG_OFFSET_CAPTURE)) {
            // Classic / Gutenberg pages: try main content region.
            if (preg_match('/<main\\b[^>]*>([\\s\\S]*?)<\\/main>/i', $html, $main)) {
                return trim($main[1]);
            }
            if (preg_match('/<article\\b[^>]*>([\\s\\S]*?)<\\/article>/i', $html, $article)) {
                return trim($article[1]);
            }

            return null;
        }

        $start = (int) $match[0][1];
        $len = strlen($html);
        $depth = 0;
        $i = $start;
        $end = null;

        while ($i < $len) {
            $nextOpen = stripos($html, '<div', $i);
            $nextClose = stripos($html, '</div>', $i);
            if ($nextClose === false) {
                break;
            }
            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $i = $nextOpen + 4;

                continue;
            }
            $depth--;
            $i = $nextClose + 6;
            if ($depth === 0) {
                $end = $i;
                break;
            }
        }

        if ($end === null) {
            return null;
        }

        return substr($html, $start, $end - $start);
    }

    /**
     * @return list<string>
     */
    private function collectElementorStylesheets(string $html, int $wpId, string $siteUrl): array
    {
        preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', $html, $linkTags);
        $urls = [];

        foreach ($linkTags[0] as $tag) {
            if (! preg_match('/href=["\']([^"\']+)["\']/i', $tag, $href)) {
                continue;
            }
            $url = html_entity_decode($href[1], ENT_QUOTES | ENT_HTML5);
            $hay = mb_strtolower($url);
            $keep = str_contains($hay, 'elementor')
                || str_contains($hay, 'hello-elementor')
                || str_contains($hay, '/uploads/elementor/css/')
                || str_contains($hay, 'font-awesome')
                || str_contains($hay, 'fontawesome')
                || preg_match('#/post-'.$wpId.'(?:-|$|\\.)#', $hay);

            if (! $keep) {
                continue;
            }

            if (str_starts_with($url, '//')) {
                $url = 'https:'.$url;
            } elseif (str_starts_with($url, '/')) {
                $url = rtrim($siteUrl, '/').$url;
            }

            $urls[] = $url;
        }

        // Always try the generated page CSS even if the <link> tag was missed.
        $urls[] = rtrim($siteUrl, '/').'/wp-content/uploads/elementor/css/post-'.$wpId.'.css';

        return array_values(array_unique($urls));
    }

    /**
     * @param  list<string>  $urls
     * @return list<string>
     */
    private function localizeStylesheets(array $urls, int $wpId): array
    {
        $localized = [];
        $dir = public_path('migrated-wp/css/'.$wpId);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($urls as $index => $url) {
            try {
                $response = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'RahbarHesabWpMigrate/1.0'])
                    ->get($url);
            } catch (\Throwable) {
                $localized[] = $url;

                continue;
            }

            if (! $response->successful()) {
                $localized[] = $url;

                continue;
            }

            $css = (string) $response->body();
            // Skip HTML error pages mistaken for CSS.
            if (str_contains(ltrim($css), '<!doctype') || str_contains(ltrim($css), '<html')) {
                continue;
            }

            $css = $this->rewriteCssAssetUrls($css, $url);
            $name = sprintf('%02d-%s.css', $index, substr(sha1($url), 0, 10));
            file_put_contents($dir.DIRECTORY_SEPARATOR.$name, $css);
            $localized[] = asset('migrated-wp/css/'.$wpId.'/'.$name);
        }

        return array_values(array_unique($localized));
    }

    private function rewriteCssAssetUrls(string $css, string $cssUrl): string
    {
        $base = preg_replace('#/[^/]*$#', '/', $cssUrl) ?: $cssUrl;
        $pattern = '#url\(([\'"]?)([^)\'"]+)\1\)#i';

        return preg_replace_callback(
            $pattern,
            function (array $m) use ($base) {
                $target = trim($m[2]);
                if ($target === '' || str_starts_with($target, 'data:') || str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
                    return $m[0];
                }
                if (str_starts_with($target, '//')) {
                    return 'url('.$m[1].'https:'.$target.$m[1].')';
                }
                if (str_starts_with($target, '/')) {
                    $origin = parse_url($base, PHP_URL_SCHEME).'://'.parse_url($base, PHP_URL_HOST);

                    return 'url('.$m[1].$origin.$target.$m[1].')';
                }

                return 'url('.$m[1].$base.$target.$m[1].')';
            },
            $css
        ) ?? $css;
    }

    private function stripDangerousHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html) ?? $html;
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;

        return $html;
    }
}
