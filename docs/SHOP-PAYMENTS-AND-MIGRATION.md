# خرید، پرداخت، کوپن، اسپات‌پلیر و مهاجرت وردپرس

> آخرین به‌روزرسانی: ۲۱ سپتامبر ۲۰۲۶  
> مخاطب: برنامه‌نویس. راهنمای کوتاه‌تر در `HANDBOOK-DEVELOPER.md`.  
> گزارش جلسه ساخت: `.roadmap/MVP-SHIPPED-2026-09-21.md` (لوکال، گیت نمی‌شود).

این سند جریان واقعی کد را توضیح می‌دهد — نه طرح ایده‌آل قدیمی با `app/Actions` و OTP روی Redis.

---

## واحد پول (تومان در دیتابیس، ریال در درگاه)

قیمت محصول، جمع سبد، تخفیف و `orders.total` همگی **تومان** ذخیره و نمایش می‌شوند.

درگاه‌های ایرانی (زیبال / زرین‌پال) مبلغ را **ریال** می‌خواهند. تبدیل فقط در لحظه درخواست به درگاه است:

```php
Money::tomanToRials($toman); // × 10
```

فایل: `app/Support/Money.php`

اگر مبلغ سفارش را بدون این تبدیل بفرستی، درگاه ۱۰ برابر کمتر شارژ می‌کند.

---

## جریان خرید از دید کاربر

```
۱. افزودن محصول      POST /cart/add/{product}
۲. سبد               GET  /cart
۳. (اختیاری) کوپن    POST /cart/coupon     DELETE /cart/coupon
۴. تسویه (لاگین)     GET  /checkout
۵. ساخت سفارش + درگاه POST /checkout
۶. بازگشت بانک       GET  /checkout/callback
۷. موفقیت            GET  /checkout/success/{order}
۸. اگر pending ماند  POST /checkout/{order}/retry   از /panel/orders
```

کنترلر: `App\Http\Controllers\Site\ShopController`  
سبد: `App\Services\CartService` (سشن مهمان + `cart_items` کاربر)  
سفارش: `App\Services\OrderService`

### نکات رفتاری مهم

- سبد **قبل از درگاه پاک نمی‌شود**. فقط بعد از `OrderService::markPaid()` برای همان کاربر پاک می‌شود (`CartService::clearForUser`).
- اگر کاربر درگاه را ببندد، سبد پر می‌ماند و یک سفارش `pending` در `/panel/orders` دارد.
- پرداخت مجدد فقط برای مالک سفارش و وضعیت `pending` یا `failed`.
- سفارش رایگان (جمع صفر بعد از تخفیف) بدون درگاه `markPaid` می‌شود.

---

## ساخت سفارش

`OrderService::createFromCart($user, $ip, $userAgent)` داخل تراکنش:

1. آیتم‌های سبد را می‌خواند؛ سبد خالی → استثنا.
2. کوپن session (`cart.coupon_code`) را با `CouponService::applied()` چک می‌کند.
3. `subtotal`، `discount`، `total` را می‌نویسد.
4. برای هر آیتم `order_items.metadata` ذخیره می‌شود:

```json
{
  "slug": "...",
  "type": "course",
  "course_ids": [1, 2]
}
```

`course_ids` از `ShopProduct::relatedCourses()` می‌آید (دوره تکی یا آیتم‌های باندل `course_bundle_items`).

---

## پرداخت موفق و تحویل

`OrderService::markPaid($order, $paymentFields = [])`:

1. اگر از قبل paid باشد، هیچ کار اضافه نمی‌کند (idempotent).
2. `lockForUpdate` روی ردیف سفارش.
3. `status=paid` و `paid_at`.
4. اگر کوپن داشته باشد `CouponService::recordUsage()` → ردیف `coupon_usages` + افزایش `used_count`.
5. `fulfill()` برای هر محصول، همه دوره‌های مرتبط را enroll می‌کند (`source=purchase`).
6. بعد از تراکنش سبد کاربر را خالی می‌کند.
7. فیلدهای شاپرک روی `payments` به‌روز می‌شود اگر آرایه آمده باشد.

انقضای ثبت‌نام: `CMS_ENROLLMENT_MONTHS` (پیش‌فرض ۱۲). مقدار `0` یعنی بدون انقضا.

---

## درگاه زیبال و زرین‌پال

پیکربندی: `config/cms.php` + `.env`

| متغیر | معنی |
|--------|------|
| `CMS_PAYMENT_GATEWAY` | `zibal` (پیش‌فرض) یا `zarinpal` |
| `ZIBAL_MERCHANT` | سندباکس: `zibal` |
| `ZARINPAL_MERCHANT_ID` | مرچنت زرین‌پال |
| `ZARINPAL_SANDBOX` | سندباکس زرین‌پال |

سرویس‌ها: `ZibalService`، `ZarinpalService`.

هر verify با قفل اتمیک:

```
Cache::lock('payment:verify:{paymentId}')
```

اگر `CACHE_STORE=redis` باشد همین قفل روی Redis می‌نشیند؛ در لوکال فعلی احتمالاً database/file است.

نتیجه تکراری موفق حساب می‌شود: زیبال `201`، زرین‌پال `101`. کال‌بک دوم دوباره enroll نمی‌کند چون `markPaid` زود برمی‌گردد.

فیلدهایی که روی `payments` پر می‌شوند: `user_id`, `card_pan`, `card_hash`, `verified_at`, `paid_at`, `error_message`, پاسخ خام درگاه.

---

## کد تخفیف

### مدل داده

- `coupons` — درصد / مبلغ ثابت، سقف، حداقل سبد، اولین خرید، بازه تاریخ، سقف کل و سقف per-user
- `coupon_targets` — محدود به محصول / دسته / کاربر، یا استثنا (`is_excluded`)
- `coupon_usages` — ثبت فقط هنگام پرداخت موفق (نه هنگام اعمال در سبد)

کد در سشن: `session('cart.coupon_code')`.

سرویس: `App\Services\CouponService`

| متد | کار |
|-----|-----|
| `apply` | اعتبارسنجی + نوشتن سشن |
| `applied` | خواندن سشن؛ اگر دیگر معتبر نباشد پاک می‌کند |
| `discountFor` | مبلغ تخفیف روی اقلام واجد شرایط |
| `recordUsage` | بعد از پرداخت |

کد سیدر `WELCOME10` حداقل سبد **۵۰۰٬۰۰۰ تومان** دارد؛ روی دوره ارزان اعمال نمی‌شود.

### UI

- سبد/چک‌اوت: `resources/views/components/cart-totals.blade.php`
- ادمین CRUD: `/admin/coupons` — `Admin\CouponController` — منو: «کدهای تخفیف»

---

## اسپات‌پلیر

جاب: `App\Jobs\IssueSpotplayerLicenseJob`

- `$tries = 3`
- backoff: ۳۰ / ۱۲۰ / ۳۰۰ ثانیه
- `WithoutOverlapping` روی `userId:courseId`
- اگر API یا `courses.spotplayer_course_id` خالی باشد جاب ساکت خارج می‌شود (تست‌ها نمی‌شکنند)

صدور از `OrderService::enrollUser` وقتی دوره Spot دارد، و از دکمه ادمین `retryLicenses`.

نمایش کلید:

- `/panel/courses` — لایسنس + لینک اپ (`SPOTPLAYER_PLAYER_URL`)
- `/checkout/success/{order}` — لایسنس‌های همان سفارش
- `/admin/orders/{id}` — لیست لایسنس کاربر/سفارش + دکمه صدور مجدد

بدون worker صف در پروداکشن لایسنس صادر نمی‌شود:

```bash
php artisan queue:work --tries=3
```

`QUEUE_CONNECTION=database` پیش‌فرض است. Horizon نصب نشده.

سینک لایسنس‌های قدیمی فقط از API اسپات هنوز کامند ندارد.

---

## عملیات ادمین سفارش و دسترسی

مسیر: `/admin/orders` و `/admin/orders/{id}`  
کنترلر: `Admin\OrderController`

| عمل | روت | اثر |
|-----|-----|-----|
| علامت پرداخت دستی | `POST /admin/orders/{order}/mark-paid` | همان `markPaid` → enrollment + جاب لایسنس |
| صدور مجدد لایسنس | `POST /admin/orders/{order}/retry-licenses` | جاب دوباره در صف |
| ثبت دسترسی | `POST /admin/orders/enroll` | `user_id`, `course_id`, `source` ∈ `manual` / `gift` / `free` |
| لغو دسترسی | `POST /admin/orders/enrollments/{id}/revoke` | `status=revoked` و `expires_at=now` |

ثبت‌نام رایگان از سایت: `GET /courses/{slug}/enroll-free` با `source=free`.

---

## احراز هویت کاربر سایت

دو گارد جدا: ادمین `cms` (`CmsAdmin`) و کاربر `web` (`User`). قاطی نکن.

### OTP (مسیر اصلی)

```
GET  /login
POST /login/otp          → SendOtpSmsJob
GET  /login/verify
POST /login/verify
```

کد هنوز در جدول `otp_verifications` (MySQL) است، نه Redis.

Throttle با Cache: ۳ ارسال / ۲ دقیقه per phone، ۸ per IP.

لاگ: جدول `otp_logs`. پاکسازی: `php artisan otp:prune-logs` (کران روزانه ۰۳:۱۵).

اگر `IPPANEL_API_KEY` خالی باشد کد در log می‌افتد.

پس از verify موفق هر دو فیلد `phone` و `mobile` پر می‌شوند؛ `mobile_verified_at` و `last_login_at` ست می‌شوند. کاربر `banned` / `suspended` وارد نمی‌شود.

### رمز عبور (مهاجرت وردپرس)

```
POST /login/password
```

شناسه: ایمیل یا موبایل. اگر `is_wp_password` باشد هش با `App\Support\WordpressPassword` (Phpass) چک می‌شود و در موفقیت به Bcrypt ارتقا می‌یابد.

---

## ریدایرکت ۳۰۱

میدلور موجود: `HandleCmsRedirects` روی جدول `cms_redirects`.

پنل: `/admin/redirects`

- ساخت تکی
- ورود دسته‌ای: هر خط `from to [code]` مثلاً `/old-course /courses/new-slug 301`

کامند مهاجرت دوره برای اسلاگ ووکامرس `/product/{slug}` هم ریدایرکت می‌سازد.

---

## مهاجرت داده وردپرس (ETL)

این **جایگزین `php artisan migrate` نیست.** مایگریشن لاراول فقط اسکیما را عوض می‌کند.

### اتصال — فقط بکاپ، نه دیتابیس لایو

```env
WP_DB_HOST=127.0.0.1
WP_DB_PORT=3306
WP_DB_DATABASE=wp_backup
WP_DB_USERNAME=root
WP_DB_PASSWORD=
WP_DB_PREFIX=wp_
```

کانکشن: `wordpress` در `config/database.php`.

سرویس: `App\Services\WordpressMigrationService`

### ترتیب اجرا

```bash
php artisan wp:migrate-users --dry-run
php artisan wp:migrate-users
php artisan wp:migrate-courses --dry-run
php artisan wp:migrate-courses
php artisan wp:migrate-orders --dry-run
php artisan wp:migrate-orders
php artisan wp:migrate-audit
```

| دستور | کار |
|--------|-----|
| `wp:migrate-users` | کاربران؛ موبایل: loginx_phone → digits_phone → billing_phone؛ هش Phpass خام + `is_wp_password=true` |
| `wp:migrate-courses` | محصولات ووکامرس → `shop_products` + `courses`؛ متای `_spotplayer_course`؛ ریدایرکت `/product/{slug}` |
| `wp:migrate-orders` | سفارش completed/processing → orders + items + enrollment |
| `wp:migrate-audit` | مقایسه تعداد WP در برابر لوکال |

حجم تقریبی دیتای لایو (برای ممیزی، نه برای این محیط): حدود ۱۱٬۴۰۰ کاربر، ۴۱ محصول، ۸٬۵۸۳ سفارش.

استراتژی برش لایو (روش B): پیش‌سینک + دلتای آخر شب + قطع کوتاه.

---

## مایگریشن لاراول همین بخش

`database/migrations/2026_09_21_000001_add_mobile_verified_at_to_users.php`

فقط ستون `users.mobile_verified_at` را اضافه می‌کند. دیتای وردپرس را جابجا نمی‌کند.

اسکیمای کوپن، شاپرک، otp_logs و باندل از ۱۲ سپتامبر موجود بود؛ این جلسه به کد وصل شد.

---

## تست خودکار و دستی

```bash
php artisan test
```

پوشش خرید: `tests/Feature/ShopCheckoutTest.php` (سبد بعد از لغو درگاه، کال‌بک دوبل، کوپن، mark-paid ادمین). درگاه با Http fake است نه زیبال واقعی.

### چک‌لیست دستی

1. دوره پولی → سبد → چک‌اوت → درگاه را ببند → سبد باید پر بماند.
2. `/panel/orders` → پرداخت مجدد.
3. مرچنت تست `ZIBAL_MERCHANT=zibal` تا انتها → `/checkout/success/{order}` و `/panel/courses`.
4. `/admin/coupons` کد بدون حداقل سبد بساز، در `/cart` اعمال کن، بعد از پرداخت `coupon_usages` را ببین.
5. `/admin/orders/{id}` پرداخت دستی → دوره در پنل کاربر.
6. `/admin/redirects` یک ۳۰۱ بساز و URL را باز کن.
7. صفحه‌ساز → «تاریخچه» (`admin.pages.revisions`).
8. `/login` فرم رمز عبور را ببین.
9. اگر بکاپ WP وصل است: dry-run سپس migrate واقعی + audit.
10. در پروداکشن: `queue:work` و scheduler (`otp:prune-logs` روزانه).

---

## نقشه کلاس‌ها

| موضوع | فایل |
|--------|------|
| ساخت/پرداخت/تحویل سفارش | `app/Services/OrderService.php` |
| سبد | `app/Services/CartService.php` |
| کوپن | `app/Services/CouponService.php` |
| زیبال / زرین‌پال | `app/Services/ZibalService.php`, `ZarinpalService.php` |
| اسپات | `app/Services/SpotPlayerService.php`, `app/Jobs/IssueSpotplayerLicenseJob.php` |
| OTP / SMS | `app/Services/OtpService.php`, `app/Jobs/SendOtpSmsJob.php` |
| ETL وردپرس | `app/Services/WordpressMigrationService.php` |
| تومان→ریال | `app/Support/Money.php` |
| Phpass | `app/Support/WordpressPassword.php` |
| چک‌اوت سایت | `app/Http/Controllers/Site/ShopController.php` |
| ورود کاربر | `app/Http/Controllers/Auth/AuthController.php` |
| سفارش ادمین | `app/Http/Controllers/Admin/OrderController.php` |
| کوپن ادمین | `app/Http/Controllers/Admin/CouponController.php` |
| ریدایرکت ادمین | `app/Http/Controllers/Admin/RedirectController.php` |
| روت‌ها | `routes/web.php` |

---

## چه چیزی عمداً در این بخش نیست

- پوشه `app/Actions` و Enumهای خرید
- ذخیره OTP در Redis و حذف `otp_verifications`
- Laravel Horizon / Octane
- Sanctum و API نوشتنی
- کامند سینک لایسنس‌های قدیمی اسپات از API
- FormRequest جدا برای سبد/پرداخت
