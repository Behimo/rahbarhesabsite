<?php

namespace App\Models;

use App\Support\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_INSTRUCTOR = 'instructor';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_SHOP_MANAGER = 'shop_manager';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
            'wp_id',
            'mobile',
            'name',
            'first_name',
            'last_name',
            'email',
            'phone',
            'password',
            'is_wp_password',
            'status',
            'role',
            'permissions',
        ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_wp_password' => 'boolean',
            'permissions' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isInstructor(): bool
    {
        return in_array($this->role, [self::ROLE_INSTRUCTOR, self::ROLE_ADMIN], true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $perms = $this->resolvedPermissions();

        return in_array($permission, $perms, true);
    }

    public function resolvedPermissions(): array
    {
        $custom = $this->permissions ?? [];

        return array_values(array_unique(array_merge(
            Permission::roleDefaults($this->role),
            $custom
        )));
    }

    public function isEnrolledIn(Course $course): bool
    {
        return $this->enrollments()
            ->where('course_id', $course->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public static function findOrCreateByPhone(string $phone, ?string $name = null): self
    {
        $local = \App\Support\PhoneNormalizer::toLocal($phone);
        $e164 = \App\Support\PhoneNormalizer::toE164($phone);

        $user = static::query()->where('phone', $local)->orWhere('phone', $e164)->first();

        if ($user) {
            return $user;
        }

        return static::query()->create([
            'name' => $name ?: 'کاربر '.substr($local, -4),
            'phone' => $local,
            'email' => null,
            'password' => Str::random(32),
            'role' => self::ROLE_USER,
        ]);
    }
}
