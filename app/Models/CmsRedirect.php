<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsRedirect extends Model
{
    protected $fillable = [
        'from_path', 'to_path', 'status_code', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
