<?php

namespace App\Services\Wordpress;

use App\Models\Category;
use App\Models\CmsRedirect;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Services\PermalinkService;
use App\Support\AccessCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WordpressImporter
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

    public function countQuestions(): int
    {
        return (int) $this->wp()->table('posts')->where('post_type', 'qa')->where('post_status', 'publish')->count();
    }

    protected function wp()
    {
        return DB::connection('wordpress');
    }

    /** @param array<int, int|string> $userIds
     * @return array<int|string, array<string, mixed>>
     */
    protected function userMetaForIds(array $userIds): array
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

    protected function regularAndSalePrice(array $meta): array
    {
        return $this->wordpressProductValues()->regularAndSalePrice($meta);
    }

    protected function minutesFromDurationLabel(?string $label): ?int
    {
        return $this->wordpressProductValues()->minutesFromDurationLabel($label);
    }

    /** @param array<int, int|string> $postIds
     * @return array<int|string, array<string, mixed>>
     */
    protected function postMetaForIds(array $postIds): array
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

    protected function resolveMobile(array $meta, string $fallback): ?string
    {
        return $this->wordpressIdentity()->resolveMobile($meta, $fallback);
    }

    /**
     * @return array<int, int> wp_user_id => paid order count
     */
    protected function paidOrderCountsByCustomer(): array
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
    protected function claimUniqueEmail(?string $email, int $wpId, array &$usedEmails): ?string
    {
        return $this->wordpressIdentity()->claimUniqueEmail($email, $wpId, $usedEmails);
    }

    /**
     * @param  array<string, int>  $winnerReleases  mobile => winner wp_id
     */
    protected function bulkReleaseWinnerMobiles(array $winnerReleases): void
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
    protected function bulkEnsureSiteRoles(array $userIds): void
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
    protected function mobileWinners(array $candidates): array
    {
        return $this->wordpressIdentity()->mobileWinners($candidates);
    }

    protected function releaseMobileFromOtherUsers(string $mobile, int $keepWpId): void
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

    protected function uniqueEmailOrNull(?string $email, int $wpId): ?string
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
    protected function rawClaimedMobile(array $meta, string $fallback): ?string
    {
        return $this->wordpressIdentity()->rawClaimedMobile($meta, $fallback);
    }

    /** @param  array<string, mixed>  $meta */
    protected function findOrCreateUserForBillingPhone(string $mobile, array $meta, bool $dryRun): ?User
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

    protected function storeWordpressPassword(int $userId, string $hash): void
    {
        if ($hash === '') {
            return;
        }

        DB::table('users')->where('id', $userId)->update([
            'password' => $hash,
            'is_wp_password' => true,
        ]);
    }

    protected function migrateOldSlugs(int $postId, string $prefix, string $slug, string $target): int
    {
        $count = 0;
        foreach ($this->wp()->table('postmeta')->where('post_id', $postId)->where('meta_key', '_wp_old_slug')->pluck('meta_value')->unique() as $old) {
            $old = $this->normalizeWpSlug((string) $old);
            if ($old !== '' && $old !== $slug) {
                $count += (int) $this->ensureRedirect(rtrim($prefix, '/').'/'.$old, $target);
            }
        }

        return $count;
    }

    public function ensureRedirect(string $from, string $to): bool
    {
        if (mb_strlen($from) > 500 || mb_strlen($to) > 500) {
            Log::warning('WordPress redirect exceeds 500 characters.', ['from' => $from, 'to' => $to]);

            return false;
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

        return true;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function applyRankMathMeta(Model $model, array $meta, ?string $wpExcerpt = null): void
    {
        $this->wordpressSeo()->applyRankMathMeta($model, $meta, $wpExcerpt, $this->resolveRankMathTemplate(...), $this->metaString(...), $this->rankMathRobots(...), $this->normalizeMediaUrl(...));
    }

    /**
     * Rank Math stores titles as templates such as "%title% %page% ... %sitename%".
     * Replace the variables used on single pages so the browser title is readable.
     */
    public function resolveRankMathTemplate(?string $template, Model $model, ?string $excerpt = null): ?string
    {
        return $this->wordpressSeo()->resolveRankMathTemplate($template, $model, $excerpt);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<string>  $keys
     */
    protected function metaString(array $meta, array $keys): ?string
    {
        return $this->wordpressSeo()->metaString($meta, $keys);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function rankMathRobots(array $meta): ?string
    {
        return $this->wordpressSeo()->rankMathRobots($meta);
    }

    protected function termRobots(int $termId): ?string
    {
        if (! Schema::connection('wordpress')->hasTable('termmeta')) {
            return null;
        }

        return $this->rankMathRobots(['rank_math_robots' => $this->wp()->table('termmeta')
            ->where('term_id', $termId)->where('meta_key', 'rank_math_robots')->value('meta_value')]);
    }

    protected function attachmentAlt(int $attachmentId): ?string
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

    protected function isSkippedCategorySlug(string $slug): bool
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
    protected function syncModelCategories(Model $model, int $wpPostId, string $taxonomy, string $categoryType): void
    {
        $map = $this->resolveCategoryMapForWpObject($wpPostId, $taxonomy, $categoryType);
        $model->syncCategories(array_values(array_unique($map)), $this->primaryCategoryId($wpPostId, $taxonomy, $map));
    }

    /**
     * Rank Math stores the permalink category as a term id; without it WordPress falls back to the lowest term id.
     *
     * @param  array<int, int>  $map
     */
    protected function primaryCategoryId(int $wpPostId, string $taxonomy, array $map): ?int
    {
        $termId = (int) $this->wp()->table('postmeta')
            ->where('post_id', $wpPostId)
            ->where('meta_key', $taxonomy === 'product_cat' ? 'rank_math_primary_product_cat' : 'rank_math_primary_category')
            ->value('meta_value');

        if ($termId > 0 && isset($map[$termId])) {
            return $map[$termId];
        }
        if ($map === []) {
            return null;
        }
        ksort($map);

        return reset($map);
    }

    /**
     * @param  Model&object{syncTags: callable}  $model
     */
    protected function syncModelTags(Model $model, int $wpPostId, string $taxonomy): void
    {
        $model->syncTags($this->resolveTagIdsForWpObject($wpPostId, $taxonomy));
    }

    /**
     * @return list<int>
     */
    protected function resolveCategoryIdsForWpObject(int $wpPostId, string $taxonomy, string $categoryType): array
    {
        return array_values(array_unique($this->resolveCategoryMapForWpObject($wpPostId, $taxonomy, $categoryType)));
    }

    /**
     * @return array<int, int> WordPress term id => Laravel category id
     */
    protected function resolveCategoryMapForWpObject(int $wpPostId, string $taxonomy, string $categoryType): array
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
                $ids[(int) $row->term_id] = $category->id;
            }
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    protected function resolveTagIdsForWpObject(int $wpPostId, string $taxonomy): array
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
    protected function wpTermsForTaxonomy(string $taxonomy): Collection
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
    protected function wpTermsForObject(int $objectId, string $taxonomy): Collection
    {
        $p = $this->wp()->getTablePrefix();

        return collect($this->wp()->select(
            "SELECT t.term_id, t.slug, t.name
             FROM {$p}term_relationships tr
             INNER JOIN {$p}term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             INNER JOIN {$p}terms t ON t.term_id = tt.term_id
             WHERE tr.object_id = ? AND tt.taxonomy = ?
             ORDER BY tr.term_order ASC, t.term_id ASC",
            [$objectId, $taxonomy]
        ));
    }

    /** @param  array<string, mixed>  $meta */
    protected function featuredImageUrl(array $meta): ?string
    {
        $thumbId = (int) ($meta['_thumbnail_id'] ?? 0);
        if ($thumbId <= 0) {
            return null;
        }

        $attachment = $this->wp()->table('posts')
            ->where('ID', $thumbId)
            ->where('post_type', 'attachment')
            ->first(['guid']);

        return $this->normalizeMediaUrl(trim((string) ($attachment->guid ?? '')));
    }

    /**
     * Store media as site-relative paths under /storage/uploads so every
     * environment serves the copy in storage/app/public/uploads.
     */
    public function normalizeMediaUrl(?string $url, int $maxLength = 191): ?string
    {
        return $this->wordpressMedia()->normalizeMediaUrl($url, $maxLength);
    }

    /**
     * Point stored HTML at /storage/uploads, whether the old address was absolute
     * or already a /wp-content/uploads path. The rest of each path stays as it is.
     */
    public function relativizeEmbeddedMediaUrls(string $html): string
    {
        return $this->wordpressMedia()->relativizeEmbeddedMediaUrls($html);
    }

    /**
     * @param  list<string>  $columns
     */
    protected function replaceStoredUploadPrefix(string $table, array $columns): int
    {
        return $this->wordpressMedia()->replaceStoredUploadPrefix($table, $columns);
    }

    /**
     * WordPress sometimes stores percent-encoded Persian in post_name;
     * Laravel route params arrive decoded, so slugs must match that form.
     */
    protected function normalizeWpSlug(string $slug): string
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
    protected function normalizeSpotplayerCourseId(mixed $raw): ?string
    {
        return $this->wordpressProductValues()->normalizeSpotplayerCourseId($raw);
    }

    protected function shouldSkipWpPage(string $slug, string $rawSlug = ''): bool
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

    protected function wpSiteUrl(): string
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
    protected function fetchRenderedWpPage(string $slug, int $wpId): ?array
    {
        return $this->wordpressRenderedPage()->fetchRenderedWpPage($slug, $wpId, $this->wpSiteUrl(), $this->extractElementorRoot(...), $this->collectElementorStylesheets(...));
    }

    protected function extractElementorRoot(string $html): ?string
    {
        return $this->wordpressRenderedPage()->extractElementorRoot($html);
    }

    /**
     * @return list<string>
     */
    protected function collectElementorStylesheets(string $html, int $wpId, string $siteUrl): array
    {
        return $this->wordpressRenderedPage()->collectElementorStylesheets($html, $wpId, $siteUrl);
    }

    /**
     * @param  list<string>  $urls
     * @return list<string>
     */
    protected function localizeStylesheets(array $urls, int $wpId): array
    {
        return $this->wordpressRenderedPage()->localizeStylesheets($urls, $wpId, $this->rewriteCssAssetUrls(...));
    }

    protected function rewriteCssAssetUrls(string $css, string $cssUrl): string
    {
        return $this->wordpressRenderedPage()->rewriteCssAssetUrls($css, $cssUrl);
    }

    // Lazy composition keeps the importers directly constructible and preserves
    // their protected override hooks without adding constructor requirements.
    protected function wordpressMedia(): WordpressMedia
    {
        return app(WordpressMedia::class);
    }

    protected function wordpressIdentity(): WordpressIdentity
    {
        return app(WordpressIdentity::class);
    }

    protected function wordpressProductValues(): WordpressProductValues
    {
        return app(WordpressProductValues::class);
    }

    protected function wordpressRenderedPage(): WordpressRenderedPage
    {
        return app(WordpressRenderedPage::class);
    }

    protected function wordpressSeo(): WordpressSeo
    {
        return app(WordpressSeo::class);
    }
}
