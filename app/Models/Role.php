<?php

namespace App\Models;

use App\Support\AccessCatalog;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'label',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function displayName(): string
    {
        return AccessCatalog::roleLabel($this->name, $this->label);
    }
}
