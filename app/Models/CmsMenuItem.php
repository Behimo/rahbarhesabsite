<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'type', 'url',
        'route_name', 'route_params', 'target', 'sort_order', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'route_params' => 'array',
            'meta' => 'array',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(CmsMenu::class, 'menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function resolveUrl(): string
    {
        return match ($this->type) {
            'route' => $this->route_name
                ? route($this->route_name, $this->route_params ?? [])
                : '#',
            'page' => isset($this->meta['slug'])
                ? route('pages.show', $this->meta['slug'])
                : '#',
            'post' => isset($this->meta['slug'])
                ? route('blog.show', $this->meta['slug'])
                : '#',
            'course' => isset($this->meta['slug'])
                ? route('courses.show', $this->meta['slug'])
                : '#',
            default => $this->url ?? '#',
        };
    }
}
