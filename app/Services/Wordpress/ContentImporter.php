<?php

namespace App\Services\Wordpress;

use App\Models\AccountingLaw;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Services\ContentUrlRewriter;
use App\Services\PermalinkService;
use App\Support\HtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentImporter extends WordpressImporter
{
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

                $this->applyRankMathMeta($cmsPost, $meta, $post->post_excerpt ?? null);
                $cmsPost->wp_post_id = (int) $post->ID;
                if (! empty($meta['_thumbnail_id'])) {
                    $cmsPost->featured_image_alt = $this->attachmentAlt((int) $meta['_thumbnail_id']) ?: $cmsPost->featured_image_alt;
                }
                $cmsPost->save();

                $cmsPost->load('categories');
                $permalink = app(PermalinkService::class)->post($cmsPost);
                $this->ensureRedirect('/?p='.$post->ID, $permalink);
                $this->migrateOldSlugs((int) $post->ID, '/'.($cmsPost->category?->slug ?: 'blog'), $slug, $permalink);

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

                $bodyHtml = HtmlSanitizer::cleanImported($bodyHtml);
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

                $this->applyRankMathMeta($cmsPage, $meta, $post->post_excerpt ?? null);
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
     * Rewrite absolute legacy media hosts already stored in local tables.
     *
     * @return array<string, int>
     */
    public function rewriteStoredMediaUrls(): array
    {
        $stats = ['posts' => 0, 'products' => 0, 'laws' => 0];

        foreach ([
            'posts' => [CmsPost::class, ['featured_image', 'og_image'], ['body']],
            'products' => [ShopProduct::class, ['featured_image', 'og_image'], ['description']],
            'laws' => [AccountingLaw::class, ['featured_image'], []],
        ] as $key => [$modelClass, $columns, $htmlColumns]) {
            if (! class_exists($modelClass)) {
                continue;
            }

            /** @var class-string<Model> $modelClass */
            $modelClass::query()->orderBy('id')->chunkById(200, function ($rows) use ($columns, $htmlColumns, $key, &$stats): void {
                foreach ($rows as $row) {
                    $dirty = false;
                    foreach ($columns as $column) {
                        if (! $row->isFillable($column)) {
                            continue;
                        }
                        $current = $row->getAttribute($column);
                        if (! is_string($current) || $current === '') {
                            continue;
                        }
                        $normalized = $this->normalizeMediaUrl($current);
                        if ($normalized !== null && $normalized !== $current) {
                            $row->setAttribute($column, $normalized);
                            $dirty = true;
                        }
                    }
                    foreach ($htmlColumns as $column) {
                        if (! $row->isFillable($column)) {
                            continue;
                        }
                        $current = $row->getAttribute($column);
                        if (! is_string($current) || $current === '') {
                            continue;
                        }
                        $rewritten = $this->relativizeEmbeddedMediaUrls($current);
                        if ($rewritten !== $current) {
                            $row->setAttribute($column, $rewritten);
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $row->save();
                        $stats[$key]++;
                    }
                }
            });
        }

        foreach ([
            'pages' => ['cms_pages', ['content', 'builder_content', 'og_image']],
            'questions' => ['cafe_questions', ['body', 'featured_image', 'og_image', 'video_url', 'video_poster']],
            'answers' => ['cafe_answers', ['body']],
            'settings' => ['cms_settings', ['value']],
        ] as $key => [$table, $columns]) {
            $stats[$key] = $this->replaceStoredUploadPrefix($table, $columns);
        }

        return $stats;
    }
}
