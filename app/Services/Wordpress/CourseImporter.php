<?php

namespace App\Services\Wordpress;

use App\Models\Category;
use App\Models\Course;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Str;

class CourseImporter extends WordpressImporter
{
    public function countProducts(): int
    {
        return (int) $this->wp()->table('posts')
            ->where('post_type', 'product')
            ->whereIn('post_status', ['publish', 'private'])
            ->count();
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
            $teacherIds = [];
            foreach ($batch as $post) {
                $teacherId = (int) ($postMeta[$post->ID]['_herowp_course_teacher'] ?? 0);
                if ($teacherId > 0) {
                    $teacherIds[$teacherId] = $teacherId;
                }
            }
            $teacherNames = $teacherIds === []
                ? collect()
                : $this->wp()->table('posts')->whereIn('ID', array_values($teacherIds))->pluck('post_title', 'ID');
            $instructorIds = $teacherNames->filter()->isEmpty()
                ? collect()
                : User::query()->whereIn('name', $teacherNames->filter()->unique()->values()->all())->pluck('id', 'name');

            foreach ($batch as $post) {
                $meta = $postMeta[$post->ID] ?? [];
                $rawSlug = (string) ($post->post_name ?: '');
                $slug = $this->normalizeWpSlug($rawSlug) ?: (Str::slug($post->post_title).'-'.$post->ID);
                [$price, $sale] = $this->regularAndSalePrice($meta);
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

                $image = $this->featuredImageUrl($meta);

                if ($product) {
                    $product->fill([
                        'slug' => $slug,
                        'title' => $post->post_title,
                        'description' => $post->post_content ?: $post->post_excerpt,
                        'price' => $price,
                        'sale_price' => $sale,
                        'type' => ShopProduct::TYPE_COURSE,
                        'featured_image' => $image,
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
                        'featured_image' => $image,
                        'is_published' => $post->post_status === 'publish',
                    ]);
                }

                $courseAttributes = [
                    'spotplayer_course_id' => $spotCourseId,
                    'level' => 'intermediate',
                ];
                $durationMinutes = $this->minutesFromDurationLabel(isset($meta['time']) ? (string) $meta['time'] : null);
                if ($durationMinutes) {
                    $courseAttributes['duration_minutes'] = $durationMinutes;
                }
                $teacherName = trim((string) ($teacherNames[(int) ($meta['_herowp_course_teacher'] ?? 0)] ?? ''));
                $instructorId = $teacherName !== '' ? ($instructorIds[$teacherName] ?? null) : null;
                if ($instructorId) {
                    $courseAttributes['instructor_id'] = $instructorId;
                }

                Course::query()->updateOrCreate(
                    ['shop_product_id' => $product->id],
                    $courseAttributes
                );

                $this->applyRankMathMeta($product, $meta, $post->post_excerpt ?? null);
                $product->wp_post_id = (int) $post->ID;
                $product->save();

                // URL parity: /product/{slug} stays canonical — only map legacy ?p= IDs.
                $this->ensureRedirect('/?p='.$post->ID, '/product/'.$slug.'/');
                if ($post->post_status === 'publish') {
                    $this->migrateOldSlugs((int) $post->ID, '/product', $slug, '/product/'.$slug.'/');
                }

                $this->syncModelCategories($product, (int) $post->ID, 'product_cat', Category::TYPE_PRODUCT);

                $product->wasRecentlyCreated ? $created++ : $updated++;
                $onProgress && $onProgress();
            }
        }

        return compact('created', 'updated');
    }
}
