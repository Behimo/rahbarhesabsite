<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsTheme extends Model
{
    protected $fillable = [
        'slug', 'name', 'version', 'manifest', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
