<?php

use App\Models\CmsPost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('type')->index();
            $table->string('name');
            $table->string('slug');
            $table->string('full_slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['type', 'full_slug']);
        });

        Schema::create('categoryables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->morphs('categoryable');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['category_id', 'categoryable_type', 'categoryable_id'], 'categoryables_unique');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });

        $this->migrateLegacyData();
        $this->dropLegacyTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categoryables');
        Schema::dropIfExists('categories');

        if (! Schema::hasTable('cms_categories')) {
            Schema::create('cms_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('cms_posts') && ! Schema::hasColumn('cms_posts', 'category_id')) {
            Schema::table('cms_posts', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('id')->constrained('cms_categories')->nullOnDelete();
            });
        }
    }

    private function migrateLegacyData(): void
    {
        $now = now();
        $categoryMap = [];

        if (Schema::hasTable('cms_categories')) {
            foreach (DB::table('cms_categories')->orderBy('id')->get() as $row) {
                $slug = $this->uniqueSlug('post', null, (string) $row->slug, $categoryMap);
                $categoryMap['legacy:'.$row->id] = DB::table('categories')->insertGetId([
                    'parent_id' => null,
                    'type' => 'post',
                    'name' => $row->name,
                    'slug' => $slug,
                    'full_slug' => $slug,
                    'description' => $row->description,
                    'sort_order' => (int) $row->sort_order,
                    'is_active' => true,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ]);
            }

            if (Schema::hasTable('cms_posts') && Schema::hasColumn('cms_posts', 'category_id')) {
                foreach (DB::table('cms_posts')->whereNotNull('category_id')->orderBy('id')->get() as $post) {
                    $categoryId = $categoryMap['legacy:'.$post->category_id] ?? null;
                    if ($categoryId) {
                        $this->attachCategory($categoryId, CmsPost::class, (int) $post->id, true, $now);
                    }
                }
            }
        }

        if (! Schema::hasTable('cms_taxonomies') || ! Schema::hasTable('cms_taxonomy_terms')) {
            return;
        }

        $taxonomies = DB::table('cms_taxonomies')->get()->keyBy('id');
        $terms = DB::table('cms_taxonomy_terms')->orderBy('id')->get();
        $pending = $terms->keyBy('id');
        $termCategory = [];
        $termTag = [];
        $guard = 0;

        while ($pending->isNotEmpty() && $guard < $terms->count() + 2) {
            $guard++;
            foreach ($pending as $termId => $term) {
                $taxonomy = $taxonomies->get($term->taxonomy_id);
                if (! $taxonomy) {
                    $pending->forget($termId);

                    continue;
                }

                $parentReady = $term->parent_id === null || isset($termCategory[$term->parent_id]) || isset($termTag[$term->parent_id]) || ! $pending->has($term->parent_id);
                if (! $parentReady && $pending->has($term->parent_id)) {
                    continue;
                }

                if ($taxonomy->slug === 'post-tag' || $taxonomy->type === 'tag') {
                    $slug = $this->uniqueTagSlug((string) $term->slug);
                    $termTag[$term->id] = DB::table('tags')->insertGetId([
                        'name' => $term->name,
                        'slug' => $slug,
                        'created_at' => $term->created_at ?? $now,
                        'updated_at' => $term->updated_at ?? $now,
                    ]);
                } else {
                    $type = $taxonomy->slug === 'post-category' ? 'post' : 'product';
                    $parentCategoryId = $term->parent_id ? ($termCategory[$term->parent_id] ?? null) : null;
                    $slug = $this->uniqueSlug($type, $parentCategoryId, (string) $term->slug, $categoryMap);
                    $parentFull = $parentCategoryId
                        ? DB::table('categories')->where('id', $parentCategoryId)->value('full_slug')
                        : null;
                    $full = $parentFull ? $parentFull.'/'.$slug : $slug;
                    $termCategory[$term->id] = DB::table('categories')->insertGetId([
                        'parent_id' => $parentCategoryId,
                        'type' => $type,
                        'name' => $term->name,
                        'slug' => $slug,
                        'full_slug' => $full,
                        'description' => $term->description,
                        'sort_order' => (int) ($term->sort_order ?? 0),
                        'is_active' => true,
                        'meta_title' => $term->meta_title ?? null,
                        'meta_description' => $term->meta_description ?? null,
                        'created_at' => $term->created_at ?? $now,
                        'updated_at' => $term->updated_at ?? $now,
                    ]);
                    $categoryMap[$type.':'.($parentCategoryId ?? 'root').':'.$slug] = $termCategory[$term->id];
                }

                $pending->forget($termId);
            }
        }

        if (Schema::hasTable('cms_termables')) {
            foreach (DB::table('cms_termables')->orderBy('id')->get() as $link) {
                if (isset($termCategory[$link->term_id])) {
                    $this->attachCategory(
                        $termCategory[$link->term_id],
                        (string) $link->termable_type,
                        (int) $link->termable_id,
                        false,
                        $link->created_at ?? $now
                    );
                }

                if (isset($termTag[$link->term_id])) {
                    $exists = DB::table('taggables')
                        ->where('tag_id', $termTag[$link->term_id])
                        ->where('taggable_type', $link->termable_type)
                        ->where('taggable_id', $link->termable_id)
                        ->exists();

                    if (! $exists) {
                        DB::table('taggables')->insert([
                            'tag_id' => $termTag[$link->term_id],
                            'taggable_type' => $link->termable_type,
                            'taggable_id' => $link->termable_id,
                        ]);
                    }
                }
            }
        }

        $this->markFirstCategoryPrimary();

        if (Schema::hasTable('coupon_targets')) {
            foreach ($termCategory as $oldId => $newId) {
                DB::table('coupon_targets')
                    ->where('target_type', 'category')
                    ->where('target_id', $oldId)
                    ->update(['target_id' => $newId]);
            }
        }
    }

    private function attachCategory(int $categoryId, string $type, int $id, bool $primary, mixed $timestamp): void
    {
        $exists = DB::table('categoryables')
            ->where('category_id', $categoryId)
            ->where('categoryable_type', $type)
            ->where('categoryable_id', $id)
            ->exists();

        if ($exists) {
            if ($primary) {
                DB::table('categoryables')
                    ->where('categoryable_type', $type)
                    ->where('categoryable_id', $id)
                    ->update(['is_primary' => false]);
                DB::table('categoryables')
                    ->where('category_id', $categoryId)
                    ->where('categoryable_type', $type)
                    ->where('categoryable_id', $id)
                    ->update(['is_primary' => true]);
            }

            return;
        }

        DB::table('categoryables')->insert([
            'category_id' => $categoryId,
            'categoryable_type' => $type,
            'categoryable_id' => $id,
            'is_primary' => $primary,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function markFirstCategoryPrimary(): void
    {
        $groups = DB::table('categoryables')
            ->select('categoryable_type', 'categoryable_id')
            ->groupBy('categoryable_type', 'categoryable_id')
            ->get();

        foreach ($groups as $group) {
            $hasPrimary = DB::table('categoryables')
                ->where('categoryable_type', $group->categoryable_type)
                ->where('categoryable_id', $group->categoryable_id)
                ->where('is_primary', true)
                ->exists();

            if ($hasPrimary) {
                continue;
            }

            $firstId = DB::table('categoryables')
                ->where('categoryable_type', $group->categoryable_type)
                ->where('categoryable_id', $group->categoryable_id)
                ->orderBy('id')
                ->value('id');

            if ($firstId) {
                DB::table('categoryables')->where('id', $firstId)->update(['is_primary' => true]);
            }
        }
    }

    /** @param  array<string, int>  $reserved */
    private function uniqueSlug(string $type, ?int $parentId, string $slug, array &$reserved): string
    {
        $base = $slug !== '' ? $slug : 'category';
        $candidate = $base;
        $i = 2;

        while (
            isset($reserved[$type.':'.($parentId ?? 'root').':'.$candidate])
            || $this->slugTaken($type, $parentId, $candidate)
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        $reserved[$type.':'.($parentId ?? 'root').':'.$candidate] = 0;

        return $candidate;
    }

    private function slugTaken(string $type, ?int $parentId, string $slug): bool
    {
        $siblings = DB::table('categories')->where('type', $type)->where('slug', $slug);
        $parentId
            ? $siblings->where('parent_id', $parentId)
            : $siblings->whereNull('parent_id');

        return $siblings->exists()
            || DB::table('categories')->where('type', $type)->where('full_slug', $this->fullSlug($type, $parentId, $slug))->exists();
    }

    private function fullSlug(string $type, ?int $parentId, string $slug): string
    {
        if (! $parentId) {
            return $slug;
        }

        $parent = DB::table('categories')->where('id', $parentId)->where('type', $type)->value('full_slug');

        return $parent ? $parent.'/'.$slug : $slug;
    }

    private function uniqueTagSlug(string $slug): string
    {
        $base = $slug !== '' ? $slug : 'tag';
        $candidate = $base;
        $i = 2;

        while (DB::table('tags')->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function dropLegacyTables(): void
    {
        if (Schema::hasTable('cms_posts') && Schema::hasColumn('cms_posts', 'category_id')) {
            Schema::table('cms_posts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }

        Schema::dropIfExists('cms_termables');
        Schema::dropIfExists('cms_taxonomy_terms');
        Schema::dropIfExists('cms_taxonomies');
        Schema::dropIfExists('cms_categories');
    }
};
