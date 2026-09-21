<?php

namespace App\Models;

use App\Support\Permission;
use App\Support\PhoneNormalizer;
use App\Support\WordpressPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
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
        'mobile_verified_at',
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
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
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

    public function licenses(): HasMany
    {
        return $this->hasMany(SpotplayerLicense::class);
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
                $q->whereNull('status')->orWhere('status', CourseEnrollment::STATUS_ACTIVE);
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function isBlocked(): bool
    {
        return in_array($this->status, ['banned', 'suspended'], true);
    }

    public function displayName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : (string) $this->name;
    }

    public function initials(): string
    {
        $first = mb_substr(trim((string) ($this->first_name ?: $this->name)), 0, 1);
        $last = mb_substr(trim((string) $this->last_name), 0, 1);

        return $last !== '' ? $first.$last : ($first !== '' ? $first : 'ر');
    }

    public function passwordMatches(string $plain): bool
    {
        $hash = (string) $this->getRawOriginal('password');

        if ($this->is_wp_password || WordpressPassword::isWordpressHash($hash)) {
            return WordpressPassword::check($plain, $hash);
        }

        return Hash::check($plain, $hash);
    }

    public function markLoggedIn(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
    }

    public function syncPhone(string $phone): void
    {
        $local = PhoneNormalizer::toLocal($phone);

        $this->forceFill([
            'phone' => $local,
            'mobile' => $local,
        ])->save();
    }

    public static function findByPhone(string $phone): ?self
    {
        $local = PhoneNormalizer::toLocal($phone);
        $e164 = PhoneNormalizer::toE164($phone);

        return static::query()
            ->where('phone', $local)
            ->orWhere('phone', $e164)
            ->orWhere('mobile', $local)
            ->orWhere('mobile', $e164)
            ->first();
    }

    public static function findOrCreateByPhone(string $phone, ?string $name = null): self
    {
        $local = PhoneNormalizer::toLocal($phone);

        $user = static::findByPhone($phone);

        if ($user) {
            if (! $user->mobile) {
                $user->forceFill(['mobile' => $local])->save();
            }

            return $user;
        }

        return static::query()->create([
            'name' => $name ?: 'کاربر '.substr($local, -4),
            'phone' => $local,
            'mobile' => $local,
            'email' => null,
            'password' => Str::random(32),
            'role' => self::ROLE_USER,
            'status' => 'active',
        ]);
    }
}
