<?php

namespace Database\Seeders\Demo\Builders;

use App\Models\CmsCategory;
use App\Models\CmsPost;
use Database\Seeders\Demo\Support\BlogCategoryDefinition;
use Database\Seeders\Demo\Support\BlogPostDefinition;
use Illuminate\Support\Collection;

final class DemoBlogBuilder
{
    /**
     * @param  list<BlogCategoryDefinition>  $definitions
     * @return Collection<string, CmsCategory>
     */
    public function syncCategories(array $definitions): Collection
    {
        return collect($definitions)
            ->mapWithKeys(function (BlogCategoryDefinition $definition) {
                $category = CmsCategory::query()->updateOrCreate(
                    ['slug' => $definition->slug],
                    [
                        'name' => $definition->name,
                        'description' => $definition->description,
                        'sort_order' => $definition->sortOrder,
                    ]
                );

                return [$definition->slug => $category];
            });
    }

    /**
     * @param  Collection<string, CmsCategory>  $categories
     * @param  list<BlogPostDefinition>  $definitions
     */
    public function syncPosts(Collection $categories, array $definitions): void
    {
        foreach ($definitions as $definition) {
            $category = $categories->get($definition->categorySlug);

            CmsPost::query()->updateOrCreate(
                ['slug' => $definition->slug],
                [
                    'category_id' => $category?->id,
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
        }
    }

    private function imageUrl(string $seed): string
    {
        return 'https://picsum.photos/seed/'.$seed.'/960/540';
    }
}
