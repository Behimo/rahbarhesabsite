<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPlugin extends Model
{
    protected $fillable = [
        'slug', 'name', 'version', 'provider_class', 'is_active', 'config',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }
}
