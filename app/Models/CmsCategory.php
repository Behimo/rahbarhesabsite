<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsCategory extends Model
{
    /** @use HasFactory<\Database\Factories\CmsCategoryFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'name', 'description', 'sort_order'];

    public function posts(): HasMany
    {
        return $this->hasMany(CmsPost::class, 'category_id');
    }
}
