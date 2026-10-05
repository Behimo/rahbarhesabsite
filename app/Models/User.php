<?php

namespace App\Models;

use App\Support\AccessCatalog;
use App\Support\PhoneNormalizer;
use App\Support\WordpressPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'wp_id',
        'mobile',
        'mobile_verified_at',
        'name',
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'phone',
        'password',
        'is_wp_password',
        'status',
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
        return $this->hasRole(AccessCatalog::ROLE_ADMIN);
    }

    public function isInstructor(): bool
    {
        return $this->hasAnyRole([AccessCatalog::ROLE_INSTRUCTOR, AccessCatalog::ROLE_ADMIN]);
    }

    public function assignRoleIfExists(string $role): void
    {
        $exists = Role::query()
            ->where('name', $role)
            ->where('guard_name', AccessCatalog::guard())
            ->exists();

        if ($exists) {
            $this->syncRoles([$role]);
        }
    }

    public function ensureSiteRole(): void
    {
        if ($this->roles()->exists()) {
            return;
        }

        $this->assignRoleIfExists(AccessCatalog::ROLE_USER);
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

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            'active' => 'فعال',
            'suspended' => 'معلق',
            'banned' => 'مسدود',
        ];
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

            $user->ensureSiteRole();

            return $user;
        }

        $user = static::query()->create([
            'name' => $name ?: 'کاربر '.substr($local, -4),
            'phone' => $local,
            'mobile' => $local,
            'email' => null,
            'password' => Str::random(32),
            'status' => 'active',
        ]);

        $user->ensureSiteRole();

        return $user;
    }
}
