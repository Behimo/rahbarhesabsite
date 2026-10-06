# گزارش ممیزی امنیتی — rahbarhesab

**فریمورک:** Laravel 12 · PHP 8.3 · spatie/laravel-permission
**دامنه بررسی:** `app/`، `routes/`، `config/`، `resources/views/`، `bootstrap/`، `database/seeders/`، `.env` / `.env.example`، `public/`، `.github/workflows/`
**تاریخ:** 2026-10-06
**روش:** بررسی کد ایستا (Static Code Review) بر اساس OWASP + مدل تهدید Laravel (کاربر مهمان، کاربر کم‌دسترس، تقلب در پرداخت، IDOR، XSS، ارتقای سطح دسترسی)

---

## ۰. وضعیت اعمال فیکس‌ها

> آخرین به‌روزرسانی: 2026-10-06 — بعد از اجرای `php artisan test` (84 passed / 1 failed از پیش موجود)

| ID | عنوان | وضعیت | تغییر اعمال‌شده |
|---|---|---|---|
| H1 | XSS از طریق JSON-LD | ✅ **رفع شد** | `layouts/site.blade.php:45` — `JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_HEX_APOS \| JSON_HEX_QUOT` جایگزین `JSON_UNESCAPED_SLASHES` شد |
| H3 | آپلود SVG | ✅ **رفع شد** | `MediaUploadRequest.php:17` — `svg` از `mimes:` حذف شد |
| H4 | نبود Rate Limiting | ✅ **رفع شد** | ۵ نرخ‌لیمیتر در `AppServiceProvider::registerRateLimiters()` + `throttle` روی `routes/web.php` و `routes/api.php` |
| H5 | Credential پیش‌فرض | ✅ **رفع شد** | `.env.example` (پسورد خالی)، `DefaultUsersSeeder` (گارد production + حذف demoها)، `CmsEnsureAdminCommand` (`firstOrCreate` + حداقل ۱۲ کاراکتر + فلگ `--reset-password`) |
| H2 | XSS محتوای HTML | ⬜ باز | نیازمند `SafeHtml` Rule + `clean_html` helper |
| M1 | `trustProxies(at: '*')` | ⬜ باز | |
| M2 | هدرهای امنیتی | ⬜ باز | `SecurityHeaders` middleware هنوز ساخته نشده |
| M3 | throttle فرم تماس | ✅ **رفع شد** | `throttle:contact` روی `POST /contact` |
| M4 | بازنویسی تنظیمات از Import | ⬜ باز | |
| M5 | SSRF درگاه زیبال | ⬜ باز | |
| M6 | پسوند فایل از کلاینت | ⬜ باز | |
| M7 | توکن API | ✅ **رفع شد** | `throttle:api` به `routes/api.php` اضافه شد؛ هنوز: `hash_equals` در `EnsureApiToken` و توکن قوی |
| M8 | scope `published()` | ⬜ باز | |
| L1–L7 | ایرادات کم‌ریسک | ⬜ باز | |

**تست رگرسیون:** `tests/Feature/SecurityRegressionTest.php` (12 تست — همه سبز)

- محدودیت نرخ ورود ادمین و کاربر
- وجود نرخ‌لیمیترهای `login` / `otp-send` / `otp-verify` / `contact` / `api`
- محدودیت نرخ فرم تماس
- رد شدن آپلود SVG / پذیرش JPG
- شکستن نکردن تگ `<script>` توسط JSON-LD
- گارد production در `DefaultUsersSeeder`
- `cms:ensure-admin` پسورد موجود را بازنویسی نمی‌کند
- رد پسورد کوتاه و خالی در `cms:ensure-admin`

**شکسته‌شدن از پیش موجود (unrelated):** `PlatformTest > free course enrollment` — روی baseline تمیز (بدون تغییرات) نیز fail می‌شود.

---


## ۱. خلاصه مدیریتی

| بخش | وضعیت | توضیح |
|---|---|---|
| SQL Injection | ✅ پاک | هیچ ورودی کاربری در کوئری خام استفاده نشده |
| IDOR / Authorization | ✅ خوب | ownership در cart/order/lesson/section پیاده شده + تست `RbacTest` |
| پرداخت | ✅ خوب | مبلغ سمت سرور، verify با API درگاه، قفل موازی، اعتبارسنجی مبلغ |
| Mass Assignment | ✅ خوب | همه‌جا از `validated()` / `*Attributes()` استفاده شده |
| OTP | ✅ نسبتاً خوب | `hash_equals`، سقف تلاش، cooldown |
| **XSS ذخیره‌شده** | ❌ **ضعیف** | ۴ مسیر خام `{!! !!}` + شکستن تگ در JSON-LD |
| **Rate Limiting** | ❌ **غایب** | هیچ `throttle` در کل پروژه تعریف نشده |
| **Credential پیش‌فرض** | ❌ **ضعیف** | پسورد ادمین و سیدرها |
| هدرهای امنیتی HTTP | ❌ غایب | CSP / X-Frame-Options / HSTS وجود ندارد |

**تعداد ایرادات:** 5 High · 8 Medium · 7 Low · 8 Informational

**اولویت اقدام:**

| # | آیتم | تلاش تقریبی |
|---|---|---|
| 1 | H1 — `JSON_HEX_TAG` در JSON-LD | ۲ دقیقه |
| 2 | H5 — پسورد ادمین در `.env` + گارد سیدر | ۱۵ دقیقه |
| 3 | H4 — `RateLimiter` برای ورود | ۳۰ دقیقه |
| 4 | H3 — حذف `svg` از `MediaUploadRequest` | ۱ دقیقه |
| 5 | H2 — سانیتایز HTML محتوای CMS | ۲–۳ ساعت |
| 6 | M1 + M2 — `trustProxies` + هدرها | ۱ ساعت |
| 7 | M4 + M5 — محدودسازی import و `base_url` | ۱ ساعت |

---

## ۲. نکات مثبت (تاییدشده)

- **پرداخت:** مبلغ کاملاً سمت سرور از `ShopProduct::effectivePrice()` محاسبه می‌شود (`app/Services/OrderService.php:33-35`)، verify با API درگاه انجام می‌شود (`ZarinpalService.php:150`، `ZibalService.php:141`)، با `Cache::lock` در برابر race محافظت شده و trait `GuardsVerifiedPaymentAmount` مبلغ گزارش‌شده را چک می‌کند.
- **RBAC:** متمرکز در `App\Support\AccessCatalog`، fail-closed (گرفتن `PermissionDoesNotExist` → `false`)، و دارای تست `tests/Feature/RbacTest.php` که مرز نقش‌ها را پوشش می‌دهد.
- **IDOR:** `ShopController::authorizeCartItem` (خط 296)، `retryPayment` / `success` با `abort_unless($order->user_id === auth()->id())`، `Admin\CourseController::updateSection/updateLesson/destroyLesson` همگی بررسی مالکیت (`abort_if`) دارند.
- **OTP:** `hash_equals`، سقف ۵ تلاش (`otp.max_attempts`)، cooldown ۶۰ ثانیه، محدودیت شماره/IP، و `peekLatestCode` فقط در `local` / `testing` (`app/Services/OtpService.php:110`).
- **Session:** `regenerate()` بعد از ورود، `invalidate()` + `regenerateToken()` هنگام خروج (هر دو کنترلر احراز هویت).
- **SQL Injection:** هیچ ورودی کاربری در `whereRaw` / `orderByRaw` / `DB::select` نیست — همه مقادیر ثابت یا binding هستند (بررسی شد: `CategoryService.php:65`، `CourseController.php:49,56,57`، `BlogController.php:34`).
- `robots.txt` مسیرهای `/admin/` و `/panel/` را disallow کرده (`app/Http/Controllers/Site/SitemapController.php:44`).
- `.env` در git tracked نیست و `.gitignore` آن را پوشش می‌دهد.
- `public/.htaccess` دایرکتوری‌لیستینگ را با `Options -MultiViews -Indexes` می‌بندد.

---

## ۳. ایرادات HIGH

---

### H1 — XSS ذخیره‌شده از طریق JSON-LD (شکستن تگ `script`)

**Risk:** High
**فایل:** `resources/views/layouts/site.blade.php:45`

```blade
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
```

**مشکل:**

`JSON_UNESCAPED_SLASHES` باعث می‌شود کاراکتر `/` اسکیپ نشود. پارسر HTML داخل عنصر `<script>` به‌دنبال اولین رشته `</script` می‌گردد، بدون توجه به اینکه داخل یک رشته JSON هستیم. نتیجه: خروجی `</script>` داخل داده، تگ اسکریپت را می‌بندد و باقی‌مانده به‌صورت HTML عادی پارس می‌شود.

**سناریوی بهره‌برداری:**

کاربر با نقش `editor` (که `create_posts` دارد) عنوان مقاله را اینطور ثبت می‌کند:

```
</script><script>fetch('/admin/settings',{method:'PUT',credentials:'include',headers:{'X-XSRF-TOKEN':decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)[1]),'Content-Type':'application/x-www-form-urlencoded'},body:'contact_email=attacker@evil.com'})</script>
```

`$schema` از `$post->title` ساخته می‌شود (`app/Http/Controllers/Site/BlogController.php:183` → `'headline' => $post->title`) و در `<head>` **هر بازدیدکننده‌ای — از جمله ادمین** — رندر می‌شود.

کوکی `XSRF-TOKEN` در Laravel به‌صورت پیش‌فرض `httpOnly` نیست، پس مهاجم توکن CSRF را می‌خواند و به‌عنوان ادمین عمل می‌کند → **ارتقای سطح دسترسی editor → admin کامل** (تنظیمات، اطلاعات درگاه، مدیریت کاربران، import/export).

**مسیرهای درگیر:**

- `resources/views/layouts/site.blade.php:45` (تنها محل `ld+json`)
- منابع داده: `BlogController::articleSchema` (عنوان/توضیح مقاله)، `SeoService::courseSchema` (عنوان دوره)، `SeoService::breadcrumbSchema` (عنوان صفحه/دوره/مقاله)

**Recommended Fix:**

```blade
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
```

**Secure Refactored Example (ترجیحی — helper مشترک):**

```php
// app/Support/helpers.php
if (! function_exists('json_ld')) {
    function json_ld(array $schema): string
    {
        return json_encode(
            $schema,
            JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        ) ?: '';
    }
}
```

```blade
<script type="application/ld+json">{!! json_ld($schema) !!}</script>
```

> `JSON_HEX_TAG` کافی است (`<` و `>` تبدیل به `\u003C` / `\u003E` می‌شوند). حذف `JSON_UNESCAPED_SLASHES` نیز اثر محافظتی دوم دارد.

---

### H2 — XSS ذخیره‌شده از محتوای HTML بدون فیلتر (ارتقای editor/instructor → admin)

**Risk:** High

**مسیرهای خام `{!! !!}` که داده تولیدشده توسط کاربر را بدون Escape خروجی می‌دهند:**

| فایل | خط | منبع داده |
|---|---|---|
| `resources/views/pages/blog/show.blade.php` | 55 | `{!! $post->body !!}` |
| `resources/views/pages/courses/learn.blade.php` | 36 | `{!! $currentLesson->content !!}` |
| `resources/views/pages/dynamic.blade.php` | 13 | `{!! $bodyHtml !!}` |
| `resources/views/pages/sections.blade.php` | 4 | `{!! $bodyHtml !!}` |
| `resources/views/admin/pages/cms-content.blade.php` | 11 | `{!! $bodyHtml ?? '' !!}` |
| `resources/views/partials/blocks/text.blade.php` | 3 | `{!! $content !!}` (richtext بلاک) |
| `resources/views/partials/blocks/html.blade.php` | 2 | `{!! $html !!}` (HTML دلخواه) |
| `resources/views/partials/blocks/columns.blade.php` | 7 | `{!! $section['html'] !!}` |
| `resources/views/admin/pages/builder.blade.php` | 80 | `{!! $canvasHtml !!}` |

**مشکل:**

هیچ‌کجا HTML سانیتایز نمی‌شود:

- `app/Http/Requests/Admin/PostRequest.php:53` → `'body' => ['nullable', 'string']`
- `app/Http/Requests/Admin/BuilderSaveRequest.php:30` → `'builder_content' => ['required', 'array']` (فقط «آرایه بودن» چک می‌شود، نه ساختار یا محتوای فیلدها)
- `app/Http/Controllers/Admin/CourseController.php:142,167` → `'content' => ['nullable', 'string']`
- `app/Blocks/TextBlock.php` و `app/Blocks/HtmlBlock.php` مقدار را عیناً به view می‌دهند

**سناریوی بهره‌برداری:**

- `instructor` (دارای `update_courses`) اسکریپت در `content` درس می‌گذارد.
- `editor` (دارای `update_posts` / `update_pages`) اسکریپت در `body` مقاله یا بلاک richtext صفحه می‌گذارد.
- هر ادمینی که همان صفحه را باز کند — حتی از طریق `admin.posts.preview` (`PostController.php:159`) — کوکی/توکن CSRF خوانده می‌شود → سرقت نشست ادمین.

**Recommended Fix (لایه‌ای):**

1. یک Rule مشترک بسازید:

```php
// app/Rules/SafeHtml.php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeHtml implements ValidationRule
{
    private const FORBIDDEN = '/<\s*(script|iframe|object|embed|form|link|meta|base|svg|math|frame|frameset)\b|on[a-z]+\s*=|javascript\s*:/i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(':attribute باید متن باشد.');

            return;
        }

        if (preg_match(self::FORBIDDEN, $value)) {
            $fail(':attribute شامل تگ یا رویداد جاوااسکریپت غیرمجاز است.');
        }
    }
}
```

2. در `PostRequest`، `CourseLessonRequest`، `BuilderSaveRequest` و `Admin\CourseController` اعمال کنید:

```php
use App\Rules\SafeHtml;

// PostRequest::rules()
'body' => ['nullable', 'string', new SafeHtml],
```

3. در لایه ذخیره‌سازی نیز پاک‌سازی کنید (دفاع در عمق):

```php
// app/Support/helpers.php
if (! function_exists('clean_html')) {
    function clean_html(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $allowed = '<p><br><b><strong><i><em><u><s><ul><ol><li>'
            . '<h1><h2><h3><h4><h5><h6><blockquote><pre><code>'
            . '<a><img><table><thead><tbody><tfoot><tr><th><td><hr>'
            . '<span><div><figure><figcaption><details><summary>';

        return strip_tags($html, $allowed);
    }
}
```

```php
$validated['body'] = clean_html($validated['body']);
```

> اگر امکان افزودن پکیج وجود دارد، `mews/purifier` (HTML Purifier) گزینه استاندارد است.

---

### H3 — آپلود SVG → XSS روی دامنه اصلی

**Risk:** High

**فایل‌ها:**

- `app/Http/Requests/Admin/MediaUploadRequest.php:24`

  ```php
  'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,svg,pdf'],
  ```

- `app/Http/Controllers/Admin/MediaController.php:41`

  ```php
  $path = $file->store('cms/'.date('Y/m'), 'public');
  ```

**مشکل:**

SVG یک سند XML است و می‌تواند `<script>` یا `onload` داخل خود داشته باشد. فایل روی دیسک `public` ذخیره و از مسیر `/storage/cms/YYYY/MM/...` سرو می‌شود. باز کردن مستقیم لینک در تب مرورگر → اجرای اسکریپت در origin اصلی سایت.

**سناریوی بهره‌برداری:**

کاربر با نقش `instructor` یا `editor` (هر دو `create_media` دارند — نگاه کنید به `AccessCatalog::rolePermissions`) فایل SVG مخرب آپلود می‌کند و لینک مستقیم را برای ادمین می‌فرستد.

**Recommended Fix:**

```php
// MediaUploadRequest::rules()
'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
```

اگر SVG واقعاً لازم است، سرو آن باید با هدر `Content-Disposition: attachment` انجام شود یا محتوا سانیتایز شود:

```php
// MediaController::store — بعد از $file->store(...)
if ($file->getMimeType() === 'image/svg+xml') {
    $fullPath = Storage::disk('public')->path($path);
    $svg = (string) file_get_contents($fullPath);

    $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg) ?? $svg;
    $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg) ?? $svg;
    $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg) ?? $svg;
    $svg = preg_replace('/\s(xlink:href|href|src)\s*=\s*("|')\s*(javascript|data:text\/html)[^"']*\2/i', '', $svg) ?? $svg;

    Storage::disk('public')->put($path, $svg);
}
```

**راه‌حل پیشنهادی:** حذف کامل `svg` از لیست مجاز.

---

### H4 — نبود Rate Limiting روی ورود (Brute Force)

**Risk:** High

**فایل‌های درگیر:**

- `routes/web.php:139` → `Route::post('/login/password', ...)`
- `routes/web.php:141` → `Route::post('/login/verify', ...)`
- `routes/web.php:170` → `Route::post('login', ...)` (پنل ادمین)
- `app/Http/Requests/Auth/PasswordLoginRequest.php`
- `app/Http/Requests/Admin/LoginRequest.php`

**مشکل:**

جستجوی کامل در `app/`، `routes/` و `bootstrap/` نشان می‌دهد **هیچ** `throttle`، `RateLimiter::for` یا `RateLimited` در پروژه تعریف نشده. تنها محدودیت نرخ، داخل `OtpService` است (`app/Services/OtpService.php:27,60-63,123-131`) که فقط کد یک‌بارمصرف را پوشش می‌دهد، نه ورود با رمز.

همچنین `Admin\LoginRequest` هیچ قانون طول رمز ندارد:

```php
// app/Http/Requests/Admin/LoginRequest.php:14
'password' => ['required'],   // بدون min
```

**سناریوی بهره‌برداری:**

با ابزار brute-force روی `POST /login/password` یا `POST /admin/login`، رمزهای کوتاه/متداول بدون هیچ محدودیتی در چند دقیقه شکسته می‌شوند. حمله credential-stuffing با لیست‌های لو رفته نیز بدون throttle ممکن است.

**Recommended Fix:**

1) `bootstrap/app.php`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

->withMiddleware(function (Middleware $middleware): void
{
    $middleware->trustProxies(at: [/* ... */]);
    $middleware->append(HandleCmsRedirects::class);
    $middleware->append(SecurityHeaders::class);

    $middleware->alias([/* ... */]);

    RateLimiter::for('login', function (Request $request) {
        $identity = $request->input('email')
            ?? $request->input('login')
            ?? $request->ip();

        return Limit::perMinute(5)
            ->by('login:'.mb_strtolower((string) $identity))
            ->by($request->ip())
            ->response(fn () => back()->withErrors([
                'login' => 'تعداد تلاش‌ها بیش از حد مجاز است. چند دقیقه بعد دوباره تلاش کنید.',
            ]));
    });

    RateLimiter::for('otp-send', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
    RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
    RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
})
```

2) `routes/web.php`:

```php
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login/otp', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:otp-send')
        ->name('login.otp');
    Route::post('/login/password', [AuthController::class, 'loginPassword'])
        ->middleware('throttle:login')
        ->name('login.password');
    Route::get('/login/verify', [AuthController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:otp-verify')
        ->name('login.verify.submit');
});
```

```php
// داخل گروه admin
Route::post('login', 'login')->middleware('throttle:login');
```

3) `app/Http/Requests/Admin/LoginRequest.php`:

```php
public function rules(): array
{
    return [
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:8'],
    ];
}
```

> نکته: حداقل `min:8` روی `PasswordLoginRequest` هم بررسی شود؛ در حال حاضر فقط `'required', 'string'` دارد.

---

### H5 — Credentialهای پیش‌فرض ادمین و سیدرهای پرخطر

**Risk:** High

**موقعیت‌ها و مقادیر تاییدشده:**

| موقعیت | مقدار |
|---|---|
| `.env.example:CMS_ADMIN_PASSWORD` | `secret` |
| `.env` فعلی این پروژه | `secret` (بررسی و تایید شد) |
| `app/Console/Commands/CmsEnsureAdminCommand.php:20-22` | fallback به `'password'` |
| `database/seeders/DefaultUsersSeeder.php:34,43,49,56` | `password` برای editor / shop / instructor / demo |
| `database/seeders/DatabaseSeeder.php` | `DefaultUsersSeeder` به‌صورت پیش‌فرض صدا زده می‌شود |
| `tests/Feature/RbacTest.php:110` | انتظار ورود با `password` |

**مشکل:**

- اگر در production دستور `php artisan db:ensure-admin` بدون تنظیم `CMS_ADMIN_PASSWORD` اجرا شود، پسورد ادمین `password` خواهد بود.
- اگر `php artisan db:seed` در production اجرا شود، ۴ حساب با پسورد `password` ساخته می‌شود که سه‌تای آن‌ها نقش **editor / shop_manager / instructor** دارند → دسترسی مستقیم و پایدار به پنل مدیریت.
- `.env.example` عملاً یک مقدار weak پیشنهاد می‌دهد و روند `composer setup` (که `.env.example` را کپی می‌کند) آن را منتقل می‌کند.

**سناریوی بهره‌برداری:**

1. استقرار بدون تنظیم رمز قوی.
2. مهاجم `editor@rahbarhesab.ir` / `password` را امتحان می‌کند → ورود به پنل.
3. از H1/H2 برای ارتقا به ادمین استفاده می‌کند.

**Recommended Fix:**

```php
// app/Console/Commands/CmsEnsureAdminCommand.php
public function handle(): int
{
    $email = mb_strtolower(trim((string) config('cms.admin_email', 'admin@rahbarhesab.ir')));
    $password = trim((string) config('cms.admin_password'));

    if ($email === '') {
        $this->error('CMS_ADMIN_EMAIL در .env تنظیم نشده است.');

        return self::FAILURE;
    }

    if ($password === '') {
        $this->error('CMS_ADMIN_PASSWORD در .env تنظیم نشده است — ادمین ساخته نشد.');

        return self::FAILURE;
    }

    if (strlen($password) < 12) {
        $this->error('CMS_ADMIN_PASSWORD باید حداقل ۱۲ کاراکتر باشد.');

        return self::FAILURE;
    }

    AccessCatalog::install();

    $admin = User::query()->firstOrCreate(
        ['email' => $email],
        [
            'name' => 'مدیر سایت',
            'password' => $password,
            'status' => 'active',
        ]
    );

    $admin->syncRoles([AccessCatalog::ROLE_ADMIN]);

    $this->info("مدیر سیستم: {$admin->email} (id: {$admin->id})");

    return self::SUCCESS;
}
```

```php
// database/seeders/DefaultUsersSeeder.php
public function run(): void
{
    if (app()->environment('production')) {
        $this->command?->warn('DefaultUsersSeeder در production اجرا نشد.');

        return;
    }

    if (! app()->environment('local', 'testing')) {
        return;
    }

    // ... بقیه کد
}
```

```dotenv
# .env.example
CMS_ADMIN_PASSWORD=
CMS_API_TOKEN=
```

**همچنین:** پسورد فعلی `secret` در `.env` را همین الان عوض کنید.

---

## ۴. ایرادات MEDIUM

---

### M1 — `trustProxies(at: '*')` → دور زدن محدودیت IP در OTP

**Risk:** Medium
**فایل:** `bootstrap/app.php:23`

```php
$middleware->trustProxies(at: '*');
```

**استفاده:**

- `app/Services/OtpService.php:26` → `$ip = (string) Request::ip();`
- `app/Services/OtpService.php:62,126,130` → کلید `otp:throttle:ip:{ip}` با سقف ۸ درخواست در ۱۲۰ ثانیه

**سناریوی بهره‌برداری:**

با ارسال `X-Forwarded-For: <مقدار تصادفی>` در هر درخواست، محدودیت IP کاملاً بی‌اثر می‌شود. همچنین فیلد `user_ip` ثبت‌شده در سفارش‌ها (`OrderService::createFromCart`) و `OtpLog.ip_address` قابل جعل است که تحقیق بعدی را مخدوش می‌کند.

**Recommended Fix:**

```php
// bootstrap/app.php
$middleware->trustProxies(at: [
    '127.0.0.1',
    '::1',
    // IPهای واقعی لودبالانسر / CDN خود را اینجا اضافه کنید
]);
```

---

### M2 — نبود هیچ‌کدام از هدرهای امنیتی HTTP

**Risk:** Medium
**فایل:** `bootstrap/app.php`

جستجو در کل `app/`، `config/` و `routes/` نشان می‌دهد `X-Frame-Options`، `Content-Security-Policy`، `X-Content-Type-Options`، `Strict-Transport-Security` و `Referrer-Policy` هیچ‌جا تنظیم نشده‌اند.

**ریسک:** Clickjacking روی فرم‌های ورود/پرداخت، MIME-sniffing، نبود HSTS، بازتاب نامناسب Referrer.

**Recommended Fix:**

```php
// app/Http/Middleware/SecurityHeaders.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->is('admin/*', 'panel/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
```

```php
// bootstrap/app.php
$middleware->append(SecurityHeaders::class);
```

> برای CSP ابتدا لیست دامنه‌های third-party را جمع‌آوری کنید: `cdn.jsdelivr.net`، `www.aparat.com`، `www.youtube.com`، `app.spotplayer.ir`، `www.aparat.com`، `i.ytimg.com`.

---

### M3 — فرم تماس بدون محدودیت نرخ → اسپم / DoS

**Risk:** Medium
**فایل:** `app/Http/Controllers/Site/ContactController.php:31`

```php
ContactMessage::query()->create($request->messageAttributes());
```

**مشکل:** هیچ throttle روی `POST /contact` نیست. `ContactMessageRequest` فقط `max:5000` روی متن دارد. مهاجم می‌تواند جدول `contact_messages` را پر کند یا از آن به‌عنوان canal اسپم استفاده کند.

**Recommended Fix:**

```php
// routes/web.php
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');
```

(نرخ‌لیمیتر `contact` از H4.)

---

### M4 — بازنویسی تنظیمات حیاتی از طریق Import

**Risk:** Medium
**فایل:** `app/Services/ImportExportService.php:24-36`

```php
foreach ($payload['settings'] ?? [] as $key => $value) {
    CmsSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
}

foreach ($payload['pages'] ?? [] as $page) {
    unset($page['id']);
    CmsPage::query()->updateOrCreate(['slug' => $page['slug']], $page);   // $guarded = ['id']
}
```

**مشکل:**

1. هر کلید `cms_settings` قابل نوشتن است — از جمله `payment_gateways` و `payment_gateway_credentials` که `ZibalService::base_url` و `merchant` را تعیین می‌کنند.
2. `pages` / `posts` با کلیدهای دلخواهِ فایل JSON و `$guarded = ['id']` mass-assign می‌شوند (`is_system`، `builder_enabled`، `status`، و غیره).
3. هیچ `DB::transaction`، هیچ اعتبارسنجی schema و هیچ لاگ ممیزی وجود ندارد.

**سناریوی بهره‌برداری:**

یک فایل JSON که شامل `payment_gateway_credentials.zibal.base_url = "https://attacker.com"` باشد، از مسیر `POST /admin/import` آپلود می‌شود. بعد از آن همه تراکنش‌های پرداخت (با `merchant` واقعی) به سرور مهاجم POST می‌شود.

> دسترسی لازم `create_transfer` است که فقط نقش `admin` دارد؛ با این حال این یک عملیات تک‌مرحله‌ای و بسیار پرریسک است.

**Recommended Fix:**

```php
// app/Services/ImportExportService.php
use Illuminate\Support\Arr;

private const ALLOWED_SETTINGS = [
    'contact_email', 'contact_phone', 'contact_address', 'contact_hours',
    'social_linkedin', 'social_telegram', 'social_instagram',
    'site_logo', 'site_favicon', 'site_og_image',
    'contact_mobile', 'contact_mobile_display', 'contact_phone_display',
];

private const PAGE_FIELDS = [
    'slug', 'title', 'template', 'status', 'published_at', 'is_published',
    'is_system', 'show_in_nav', 'sort_order', 'content', 'builder_content',
    'builder_enabled', 'meta_title', 'meta_description', 'meta_keywords',
    'og_image', 'robots',
];

private const POST_FIELDS = [
    'slug', 'title', 'excerpt', 'body', 'status', 'published_at', 'is_published',
    'featured_image', 'featured_image_alt', 'author', 'reading_time_minutes',
    'meta_title', 'meta_description', 'meta_keywords', 'og_image',
    'category_id', 'views',
];

public function import(array $payload): void
{
    \DB::transaction(function () use ($payload) {
        foreach ($payload['settings'] ?? [] as $key => $value) {
            if (! in_array($key, self::ALLOWED_SETTINGS, true) || ! is_scalar($value)) {
                continue;   // payment_gateways / payment_gateway_credentials هرگز
            }

            CmsSetting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        foreach ($payload['pages'] ?? [] as $page) {
            if (! is_array($page) || ! isset($page['slug'], $page['title'])) {
                continue;
            }

            CmsPage::query()->updateOrCreate(
                ['slug' => (string) $page['slug']],
                Arr::only($page, self::PAGE_FIELDS)
            );
        }

        foreach ($payload['posts'] ?? [] as $post) {
            if (! is_array($post) || ! isset($post['slug'], $post['title'])) {
                continue;
            }

            CmsPost::query()->updateOrCreate(
                ['slug' => (string) $post['slug']],
                Arr::only($post, self::POST_FIELDS)
            );
        }
    });
}
```

و در `ImportExportController::import` یک لاگ ممیزی اضافه کنید:

```php
app(AuditService::class)->record('import', [
    'file' => $request->file('export_file')->getClientOriginalName(),
    'size' => $request->file('export_file')->getSize(),
]);
```

---

### M5 — `base_url` درگاه زیبال قابل تنظیم → SSRF / نشت اعتبار

**Risk:** Medium
**فایل:** `app/Services/ZibalService.php:194-197` و `62` / `141`

```php
private function baseUrl(): string
{
    return rtrim((string) $this->setting('base_url', 'https://gateway.zibal.ir'), '/');
}

// ...
Http::post($baseUrl.'/v1/request', ['merchant' => $merchant, ...]);
Http::post($baseUrl.'/v1/verify',  ['merchant' => $this->setting('merchant'), ...]);
```

**مشکل:** `merchant` و `trackId` به هر URL‌ای که ادمین (یا ایمپورتِ M4) مشخص کند POST می‌شود → نشت مرچنت‌کد به سرور مهاجم، یا SSRF به داخل شبکه (مثل `http://169.254.169.254/`).

**Recommended Fix:**

```php
// app/Services/ZibalService.php
private const ALLOWED_HOSTS = ['gateway.zibal.ir', 'api.zibal.ir', 'sandbox.zibal.ir'];

private function baseUrl(): string
{
    $url = rtrim((string) $this->setting('base_url', 'https://gateway.zibal.ir'), '/');
    $host = (string) parse_url($url, PHP_URL_HOST);

    if (! in_array($host, self::ALLOWED_HOSTS, true)) {
        \Log::warning('Zibal base_url rejected — falling back to default.', ['url' => $url]);

        return 'https://gateway.zibal.ir';
    }

    return $url;
}
```

برای `ZarinpalService` نیز چون `base_url` از config خوانده می‌شود و در فیلدها فقط `merchant_id` / `sandbox` قابل تنظیم است، ریسک کمتر است؛ با این حال اگر در آینده فیلد URL اضافه شد همان الگو را اعمال کنید.

---

### M6 — پسوند فایل از کلاینت گرفته می‌شود

**Risk:** Medium
**فایل:** `app/Http/Controllers/Admin/ProductController.php:74-80`

```php
$extension = $file->getClientOriginalExtension() ?: 'webp';
return $file->storeAs('cms/products', $slug.'-dashboard.'.$extension, 'public');
```

**مشکل:**

اگرچه قانون `mimes:jpg,jpeg,png,webp` محتوای فایل را محدود می‌کند، ولی **نام** فایل توسط مهاجم کنترل می‌شود. یک فایل JPEG با پسوند `php` در `public/storage/cms/products/` ذخیره می‌شود؛ در صورتی که وب‌سرور فایل‌های `.php` زیر `storage` را execute کند (پیکربندی اشتباه Apache/Laragon)، یک polyglot قابل بهره‌برداری است.

`app/Http/Controllers/Admin/MediaController.php:46` نیز نام اصلی فایل را در فیلد `filename` ذخیره می‌کند (فقط metadata است، ریسک کم).

**Recommended Fix:**

```php
// app/Http/Controllers/Admin/ProductController.php
private function storeDashboardImage(UploadedFile $file, string $slug): string
{
    $extension = strtolower((string) $file->guessExtension());   // از MIME، نه نام کلاینت
    $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'webp';

    return $file->storeAs('cms/products', $slug.'-dashboard.'.$extension, 'public');
}
```

همچنین بهتر است نام مقصد تصادفی باشد نه بر اساس slug:

```php
return $file->store('cms/products', 'public');
```

---

### M7 — توکن API پیش‌فرض + مقایسه غیر ثابت‌زمان + بدون throttle

**Risk:** Medium

**فایل‌ها:**

- `.env` → `CMS_API_TOKEN=change-me` (بررسی و تایید شد)
- `app/Http/Middleware/EnsureApiToken.php:13`

  ```php
  if (! $token || $request->bearerToken() !== $token) {
      abort(401, 'Unauthorized');
  }
  ```

**مشکل:**

1. اگر production نیز مقدار `change-me` داشته باشد، `/api/v1/*` بدون احراز هویت واقعی باز است (خروجی: محتوای منتشرشده صفحات/مقالات/دوره‌ها — ریسک متوسط).
2. `!==` روی رشته ثابت‌زمان نیست → تایمینگ ساید چنل برای حدس توکن.
3. هیچ throttle روی `/api/v1/*` نیست → اسکرپینگ و DoS.

**Recommended Fix:**

```php
// app/Http/Middleware/EnsureApiToken.php
public function handle(Request $request, Closure $next): Response
{
    $token = (string) config('cms.api_token');
    $provided = (string) $request->bearerToken();

    if ($token === '' || strlen($token) < 32 || ! hash_equals($token, $provided)) {
        abort(401, 'Unauthorized');
    }

    return $next($request);
}
```

```php
// routes/api.php
Route::prefix('v1')->middleware(['api.token', 'throttle:api'])->group(function () {
    // ...
});
```

```bash
# تولید توکن قوی برای production
php artisan tinker --execute="echo \Illuminate\Support\Str::random(64);"
```

---

### M8 — صفحات سیستمی Draft همچنان نمایش داده می‌شوند

**Risk:** Medium
**فایل:** `app/Http/Controllers/Site/SiteController.php:27`

```php
$page = $data['page'] ?? CmsPage::query()->where('slug', $slug)->first();
```

**مشکل:** scope پیش‌فرض `published()` استفاده نشده (برخلاف `SiteDataService::dynamicPage` که درست است). اگر ادمین صفحه `about` یا `contact` را به draft تغییر دهد، همچنان برای عموم رندر می‌شود.

**Recommended Fix:**

```php
$page = $data['page'] ?? CmsPage::query()->published()->where('slug', $slug)->first();
```

---

## ۵. ایرادات LOW

---

### L1 — Open Redirect از طریق ریدایرکت‌های CMS

**Risk:** Low

**فایل‌ها:**

- `app/Http/Middleware/HandleCmsRedirects.php:37`

  ```php
  return redirect($redirect->to_path, $redirect->status_code);
  ```

- `app/Http/Controllers/Admin/RedirectController.php:55-70` (import) → هر مقصدی پذیرفته می‌شود
- `app/Http/Requests/Admin/RedirectRequest.php:26` → فقط `required|string|max:255` (بدون بررسی internal/external)

**سناریوی بهره‌برداری:**

ثبت ریدایرکت `from_path = /` و `to_path = https://evil.com` → همه بازدیدکنندگان صفحه اصلی به سایت مهاجم منتقل می‌شوند (فیشینگ). از `RedirectController::import` نیز می‌توان دسته‌ای از این ریدایرکت‌ها ساخت.

**Recommended Fix:**

```php
// app/Http/Requests/Admin/RedirectRequest.php
public function withValidator(Validator $validator): void
{
    $validator->after(function (Validator $validator) {
        $to = trim((string) $this->input('to_path'));

        if ($to === '') {
            return;
        }

        // فقط مسیرهای داخلی، نه protocol-relative و نه URL خارجی
        if (! str_starts_with($to, '/') || str_starts_with($to, '//')) {
            $validator->errors()->add('to_path', 'مقصد باید مسیر داخلی سایت باشد (با / شروع شود).');
        }
    });
}
```

---

### L2 — تغییر وضعیت از طریق GET (بدون محافظت CSRF)

**Risk:** Low
**فایل:** `routes/web.php:80`

```php
Route::get('/{slug}/enroll-free', 'enrollFree')->name('enroll-free');
```

`enrollFree` (`app/Http/Controllers/Site/CourseController.php:286`) یک رکورد `CourseEnrollment` ایجاد می‌کند. با قرار دادن

```html
<img src="https://yoursite.com/courses/free-course/enroll-free">
```

در یک صفحه خارجی، هر کاربر لاگین‌شده‌ای بدون اراده در دوره ثبت‌نام می‌شود.

**Recommended Fix:**

```php
Route::post('/{slug}/enroll-free', 'enrollFree')->name('enroll-free');
```

و در view مربوطه فرم با `@csrf` جایگزین لینک شود.

> توجه: مسیرهای `GET /courses/{slug}/lessons/{lessonSlug}/file` و `GET .../download` نیز state-changing هستند ولی با امضای زمان‌دار (`URL::temporarySignedRoute`) محافظت شده‌اند — این درست است.

---

### L3 — مدل `Payment` فیلدهای حساس را مخفی نمی‌کند

**Risk:** Low
**فایل:** `app/Models/Payment.php`

`card_pan`، `card_hash`، `gateway_payload` و `gateway_response` در `$hidden` نیستند. در حال حاضر در هیچ endpoint بازگردانده نمی‌شوند، ولی کافی است یک `return response()->json($order->load('payment'))` اضافه شود تا شماره کارت و payload درگاه لو برود.

**Recommended Fix:**

```php
protected $hidden = [
    'card_pan',
    'card_hash',
    'gateway_payload',
    'gateway_response',
];
```

(و در صورت نیاز یک accessor ماسک‌شده برای نمایش در پنل ادمین اضافه کنید: `cardMaskedAttribute()` → `6274-****-****-1234`.)

---

### L4 — `instructor_id` فقط `exists:users,id` است

**Risk:** Low

**فایل‌ها:**

- `app/Http/Requests/Admin/CourseRequest.php:268`
- `app/Http/Controllers/Admin/CourseController.php:215`

```php
'instructor_id' => ['nullable', 'exists:users,id'],
```

**مشکل:** می‌توان هر کاربر عادی یا حتی مسدود (`status = banned`) را به‌عنوان مدرس دوره انتخاب کرد.

**Recommended Fix:**

```php
// بعد از validation، در validateCourse()
if (! empty($courseData['instructor_id'])) {
    $instructor = User::query()->find($courseData['instructor_id']);

    abort_unless(
        $instructor
        && ! $instructor->isBlocked()
        && $instructor->hasAnyRole([AccessCatalog::ROLE_INSTRUCTOR, AccessCatalog::ROLE_ADMIN]),
        422,
        'کاربر انتخاب‌شده مدرس معتبر نیست.'
    );
}
```

---

### L5 — `cms:ensure-admin` در هر اجرا پسورد ادمین را ریست می‌کند

**Risk:** Low
**فایل:** `app/Console/Commands/CmsEnsureAdminCommand.php:32-39`

```php
$admin = User::query()->updateOrCreate(
    ['email' => $email],
    ['name' => 'مدیر سایت', 'password' => $password, 'status' => 'active']
);
```

**مشکل:** اگر `CMS_ADMIN_PASSWORD` در production ضعیف باشد، هر بار که این کامند اجرا شود پسورد به مقدار ضعیف برمی‌گردد — حتی اگر ادمین آن را عوض کرده باشد.

**Recommended Fix:**

```php
$admin = User::query()->firstOrCreate(
    ['email' => $email],
    [
        'name' => 'مدیر سایت',
        'password' => $password,
        'status' => 'active',
    ]
);

// تغییر پسورد فقط با فلگ صریح
if ($this->option('reset-password') && $password !== '') {
    $admin->forceFill(['password' => $password])->save();
}
```

```php
protected $signature = 'cms:ensure-admin {--reset-password}';
```

---

### L6 — لینک‌های `target="_blank"` بدون `rel="noopener"`

**Risk:** Low

**فایل‌ها (15 مورد):**

- `resources/views/layouts/vuexy/sections/footer/footer-front.blade.php:25,28,31,34,37,57,80,84,87,90,93`
- `resources/views/layouts/vuexy/sections/footer/footer-admin.blade.php:10`
- `resources/views/admin/courses/index.blade.php:44`
- `resources/views/admin/posts/index.blade.php:126`
- `resources/views/admin/products/index.blade.php:41`

**ریسک:** Tabnabbing — صفحه بازشده می‌تواند از طریق `window.opener` صفحه مبدأ را جایگزین کند.

**Recommended Fix:**

```html
<a href="..." target="_blank" rel="noopener noreferrer">...</a>
```

---

### L7 — هش قدیمی وردپرس (`$P$` مبتنی بر MD5)

**Risk:** Low
**فایل:** `app/Support/WordpressPassword.php`

`check()` با تکرار MD5 کار می‌کند (الگوریتم phpass). `AuthController::loginPassword` (خطوط 128–133) هنگام اولین ورود موفق، هش را به bcrypt تبدیل می‌کند — این درست است.

با این حال `User::passwordMatches()` (خط 148–157) تا زمان اولین ورود، هش ضعیف را می‌پذیرد و کاربرانی که هرگز وارد نشده‌اند همچنان با هش MD5 ذخیره می‌شوند.

**Recommended Fix:**

یک کامند مهاجرت بسازید که کاربران دارای `is_wp_password = true` را شناسایی و در پنل ادمین نشان دهد، یا برای حساب‌های حساس reset رمز اجباری کنید:

```bash
php artisan users:wp-hash-report   # لیست کاربران با هش قدیمی
```

---

## ۶. ایرادات INFORMATIONAL

| # | مورد | فایل/مکان | توضیح |
|---|---|---|---|
| I1 | **نبود `.htaccess` در ریشه** | ریشه پروژه | اگر `DocumentRoot` ویهوست `public/` نباشد، `.env`، `vendor/`، `config/` و `storage/logs` مستقیماً دانلود می‌شوند. حتماً پیکربندی Apache/Laragon را بررسی کنید. |
| I2 | `public/hot` (آدرس Vite dev) | `public/hot` | محتوا: `http://127.0.0.1:5173`. نباید در production deploy شود؛ در `.gitignore` نیست. |
| I3 | `APP_ENV=local` و `APP_DEBUG=true` | `.env` | مطمئن شوید production مقدار متفاوت دارد (`APP_DEBUG=false`). |
| I4 | `Gate::before` عمومی | `app/Providers/AppServiceProvider.php:46-52` | به همه abilityها برای نقش `admin` پاسخ `true` می‌دهد. الان مشکلی نیست (هیچ Policy وجود ندارد)، ولی هر Policy آینده برای ادمین bypass می‌شود. |
| I5 | `SESSION_SECURE_COOKIE` تنظیم نشده | `config/session.php:157` | کوکی session روی HTTP هم ارسال می‌شود. `URL::forceScheme('https')` فقط تولید URL را تغییر می‌دهد، نه رفتار کوکی را. مقدار `.env` را `SESSION_SECURE_COOKIE=true` کنید. |
| I6 | شکست entity در هایلایت جستجو | `resources/views/pages/search.blade.php:18-22` | رگکس می‌تواند `&lt;` را بشکند. فقط ظاهری است و XSS نیست (چون `e()` قبل اعمال می‌شود). |
| I7 | نبود `config/cors.php` | `config/` | پیش‌فرض Laravel مسیر `api/*` را با `allowed_origins: *` باز می‌کند. چون `bearer token` لازم است قابل قبول است، ولی بهتر است صریح تعریف شود. |
| I8 | میدل‌ور `verified` هیچ‌جا استفاده نشده | `routes/` | `mobile_verified_at` / `email_verified_at` هیچ مسیری را محافظت نمی‌کنند. |
| I9 | مقایسه `EnsureUser` alias | `bootstrap/app.php:29` | alias `auth.user` تعریف شده ولی در هیچ route استفاده نشده (مرده). |
| I10 | `target="_blank"` در بوت‌استراپ/Vuexy | `resources/views/layouts/vuexy/**` | نسخه تجاری Vuexy؛ در upgrage آینده بررسی شود. |

---

## ۷. جدول کامل ایرادات

| ID | عنوان | Risk | فایل اصلی |
|---|---|---|---|
| H1 | XSS از طریق JSON-LD با `JSON_UNESCAPED_SLASHES` | **High** | `resources/views/layouts/site.blade.php:45` |
| H2 | XSS ذخیره‌شده از محتوای HTML بدون سانیتایز | **High** | `pages/blog/show.blade.php:55`، `pages/courses/learn.blade.php:36`، بلاک‌ها |
| H3 | آپلود SVG → XSS روی origin اصلی | **High** | `app/Http/Requests/Admin/MediaUploadRequest.php:24` |
| H4 | نبود Rate Limiting روی ورود | **High** | `routes/web.php:139,170` |
| H5 | Credential پیش‌فرض ادمین و سیدرها | **High** | `.env.example`، `CmsEnsureAdminCommand.php:20`، `DefaultUsersSeeder.php` |
| M1 | `trustProxies(at: '*')` → دور زدن throttle IP | Medium | `bootstrap/app.php:23` |
| M2 | نبود هدرهای امنیتی HTTP | Medium | `bootstrap/app.php` |
| M3 | فرم تماس بدون throttle | Medium | `Site/ContactController.php:31` |
| M4 | بازنویسی تنظیمات حیاتی از Import | Medium | `app/Services/ImportExportService.php:24` |
| M5 | `base_url` درگاه → SSRF / نشت credential | Medium | `app/Services/ZibalService.php:194` |
| M6 | پسوند فایل از کلاینت | Medium | `Admin/ProductController.php:76` |
| M7 | توکن API پیش‌فرض + مقایسه غیر ثابت‌زمان | Medium | `.env`، `EnsureApiToken.php:13` |
| M8 | صفحات سیستمی draft نمایش داده می‌شوند | Medium | `Site/SiteController.php:27` |
| L1 | Open Redirect در ریدایرکت‌های CMS | Low | `HandleCmsRedirects.php:37`، `RedirectController.php:55` |
| L2 | تغییر وضعیت از طریق GET (`enroll-free`) | Low | `routes/web.php:80` |
| L3 | مدل `Payment` بدون `$hidden` | Low | `app/Models/Payment.php` |
| L4 | `instructor_id` بدون بررسی نقش | Low | `Admin/CourseController.php:215` |
| L5 | ریست پسورد ادمین در هر اجرای کامند | Low | `CmsEnsureAdminCommand.php:32` |
| L6 | `target="_blank"` بدون `rel="noopener"` | Low | 15 فایل blade |
| L7 | هش قدیمی وردپرس `$P$` | Low | `app/Support/WordpressPassword.php` |
| I1–I10 | موارد اطلاعاتی | Info | بخش ۶ |

---

## ۸. چک‌لیست اقدام پس از اعمال فیکس‌ها

```bash
# 1) اجرای تست‌ها برای اطمینان از سلامت RBAC و checkout
composer test
# یا
php artisan config:clear && php artisan test

# 2) بررسی اینکه هیچ `{!! !!}` خام جدیدی اضافه نشده
# (grep دستی در resources/views)

# 3) تولید توکن API قوی و پسورد ادمین قوی
php artisan tinker --execute="echo \Illuminate\Support\Str::random(64);"

# 4) بررسی اینکه public/hot در build production نیست
```

**قبل از استقرار:**

- [ ] `CMS_ADMIN_PASSWORD` حداقل ۱۲ کاراکتر و متفاوت از `secret`
- [ ] `CMS_API_TOKEN` طول ≥ 32 و متفاوت از `change-me`
- [ ] `APP_DEBUG=false` و `APP_ENV=production`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `DocumentRoot` ویهوست = `public/`
- [ ] حذف `public/hot`
- [ ] `php artisan db:seed` در production اجرا **نشود**
- [ ] حذف `svg` از لیست مجاز آپلود
- [ ] تایید IPهای proxy واقعی به‌جای `*`

---

## ۹. محدودیت‌های این گزارش

- این گزارش بر اساس **بررسی کد ایستا** است؛ جایگزین تست نفوذ زنده، تست خودکار DAST، یا بازبینی پیکربندی وب‌سرور/DB/شبکه نیست.
- وضعیت `production` فرض نشده است؛ مقادیر `.env` گزارش‌شده مربوط به محیط `local` فعلی است.
- صحت عملکرد درگاه‌های پرداخت در sandbox ارائه‌دهنده تست نشده — فقط منطق کد بررسی شده.
- وابستگی‌های第三者 (`vendor/`) از نظر CVE اسکن نشده‌اند؛ پیشنهاد: `composer audit` و `npm audit`.
- پس از اعمال فیکس‌ها، `php artisan test` را اجرا کنید تا `RbacTest`، `ShopCheckoutTest` و `PopupTest` مطمئن شوند چیزی نشکسته.
