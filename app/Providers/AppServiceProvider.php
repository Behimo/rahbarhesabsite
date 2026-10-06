<?php

namespace App\Providers;

use App\Helpers\Helpers;
use App\Models\Category;
use App\Models\User;
use App\Observers\CategoryObserver;
use App\Services\BlockRegistry;
use App\Services\BlockRenderer;
use App\Services\CacheService;
use App\Services\CategoryService;
use App\Services\MenuService;
use App\Services\PageBuilderService;
use App\Services\PageRenderContext;
use App\Services\PaymentGatewayRegistry;
use App\Services\SpotPlayerService;
use App\Support\AccessCatalog;
use App\Support\PhoneNormalizer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');

        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(PaymentGatewayRegistry::class);
        $this->app->singleton(BlockRenderer::class);
        $this->app->singleton(PageRenderContext::class);
        $this->app->singleton(PageBuilderService::class);
        $this->app->singleton(CacheService::class);
        $this->app->singleton(MenuService::class);
        $this->app->singleton(CategoryService::class);
        $this->app->singleton(SpotPlayerService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Category::observe(CategoryObserver::class);

        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            return $user->hasRole(AccessCatalog::ROLE_ADMIN) ? true : null;
        });

        $this->registerRateLimiters();

        if (! class_exists('Helper', false)) {
            class_alias(Helpers::class, 'Helper');
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $limits = [
                Limit::perMinute(30)->by('login:ip:'.$request->ip()),
            ];

            $identity = $this->loginThrottleIdentity($request);

            if ($identity !== null) {
                $limits[] = Limit::perMinute(5)->by('login:by:'.$identity);
            }

            return $limits;
        });

        RateLimiter::for('otp-send', function (Request $request) {
            return $this->phoneAndIpLimits('otp-send', $request, 6);
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return $this->phoneAndIpLimits('otp-verify', $request, 12);
        });

        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinute(3)->by('contact:ip:'.$request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by('api:ip:'.$request->ip());
        });
    }

    /**
     * @return array<int, Limit>
     */
    protected function phoneAndIpLimits(string $name, Request $request, int $perMinute): array
    {
        $limits = [
            Limit::perMinute($perMinute)->by($name.':ip:'.$request->ip()),
        ];

        $phone = $this->stringInput($request, 'phone');

        if ($phone !== null && PhoneNormalizer::isValidIranMobile($phone)) {
            $limits[] = Limit::perMinute($perMinute)->by($name.':phone:'.PhoneNormalizer::toLocal($phone));
        }

        return $limits;
    }

    protected function loginThrottleIdentity(Request $request): ?string
    {
        $email = $this->stringInput($request, 'email');

        if ($email !== null) {
            return mb_strtolower($this->limitKeyFragment($email));
        }

        $login = $this->stringInput($request, 'login');

        if ($login !== null) {
            return $this->identityFragment($login);
        }

        $phone = $this->stringInput($request, 'phone');

        if ($phone !== null) {
            return $this->identityFragment($phone);
        }

        return null;
    }

    protected function identityFragment(string $value): string
    {
        if (PhoneNormalizer::isValidIranMobile($value)) {
            return PhoneNormalizer::toLocal($value);
        }

        return mb_strtolower($this->limitKeyFragment($value));
    }

    protected function stringInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function limitKeyFragment(string $value): string
    {
        return mb_substr($value, 0, 255);
    }
}
