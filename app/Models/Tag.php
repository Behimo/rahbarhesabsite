<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(CmsPost::class, 'taggable');
    }

    public function shopProducts(): MorphToMany
    {
        return $this->morphedByMany(ShopProduct::class, 'taggable');
    }
}
