<?php

namespace Database\Seeders\Demo\Builders;

use App\Models\Category;
use App\Models\CmsPost;
use Database\Seeders\Demo\Support\BlogCategoryDefinition;
use Database\Seeders\Demo\Support\BlogPostDefinition;
use Illuminate\Support\Collection;

final class DemoBlogBuilder
{
    /**
     * @param  list<BlogCategoryDefinition>  $definitions
     * @return Collection<string, Category>
     */
    public function syncCategories(array $definitions): Collection
    {
        return collect($definitions)
            ->mapWithKeys(function (BlogCategoryDefinition $definition) {
                $category = Category::query()->updateOrCreate(
                    ['type' => Category::TYPE_POST, 'slug' => $definition->slug, 'parent_id' => null],
                    [
                        'name' => $definition->name,
                        'description' => $definition->description,
                        'sort_order' => $definition->sortOrder,
                        'is_active' => true,
                    ]
                );

                return [$definition->slug => $category];
            });
    }

    /**
     * @param  Collection<string, Category>  $categories
     * @param  list<BlogPostDefinition>  $definitions
     */
    public function syncPosts(Collection $categories, array $definitions): void
    {
        foreach ($definitions as $definition) {
            $category = $categories->get($definition->categorySlug);

            $post = CmsPost::query()->updateOrCreate(
                ['slug' => $definition->slug],
                [
                    'title' => $definition->title,
                    'excerpt' => $definition->excerpt,
                    'body' => $definition->body,
                    'featured_image' => $this->imageUrl($definition->slug),
                    'author' => $definition->author,
                    'meta_title' => $definition->title.' | بلاگ راهبر حساب',
                    'meta_description' => $definition->excerpt,
                    'is_published' => true,
                    'status' => 'published',
                    'published_at' => now()->subDays($definition->daysAgo)->setTime(10, 0),
                    'views' => $definition->views,
                ]
            );

            $post->syncCategories($category ? [$category->id] : [], $category?->id);
        }
    }

    private function imageUrl(string $seed): string
    {
        return 'https://picsum.photos/seed/'.$seed.'/960/540';
    }
}
