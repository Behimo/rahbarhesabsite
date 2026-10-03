<?php

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /** @param  array<int, int|string>  $tagIds */
    public function syncTags(array $tagIds): void
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($tagIds))));
        $this->tags()->sync($ids);
    }
}
