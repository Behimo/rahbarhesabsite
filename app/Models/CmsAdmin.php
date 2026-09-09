<?php

namespace App\Models;

use App\Support\Permission;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CmsAdmin extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'permissions', 'is_super'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'permissions' => 'array',
            'is_super' => 'boolean',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_super || $this->role === 'admin') {
            return true;
        }

        $permissions = $this->permissions ?? Permission::roleDefaults($this->role ?? 'editor');

        return in_array($permission, $permissions, true);
    }

    public function canAccess(string $permission): bool
    {
        return $this->hasPermission($permission);
    }
}
