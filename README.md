# راهبر حساب (Rahbar Hesab)

پلتفرم آموزشی حسابداری و مالیات با CMS اختصاصی، فروش دوره، پرداخت آنلاین و پنل هنرجو.

**Stack:** Laravel 12 · PHP 8.2+ · Blade · Alpine.js · Vite · تم `themes/rahbarhesab`

## راه‌اندازی سریع

```sh
cp .env.example .env
composer install
php artisan key:generate
# در .env اتصال دیتابیس را تنظیم کنید (MySQL یا SQLite)
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

- سایت: `APP_URL` (مثلاً `http://rahbarhesabsite.test`)
- پنل ادمین CMS: `/admin/login`
- پنل هنرجو: `/panel` (بعد از ورود در سایت)

جزئیات بیشتر: [`docs/HANDBOOK-DEVELOPER.md`](docs/HANDBOOK-DEVELOPER.md) · [`docs/CMS-PLATFORM.md`](docs/CMS-PLATFORM.md)

## حساب‌های تستی (بعد از `migrate --seed`)

این اکانت‌ها در سیدرها ساخته می‌شوند. فقط برای محیط local/dev استفاده کنید.

### پنل ادمین CMS (`/admin/login`)

جدول `cms_admins` — از `CmsSeeder` و متغیرهای `.env`:

| نقش | ایمیل | رمز |
|---|---|---|
| سوپرادمین CMS | `admin@rahbarhesab.ir` | مقدار `CMS_ADMIN_PASSWORD` در `.env` (در `.env.example`: `secret`) |

اگر `CMS_ADMIN_EMAIL` / `CMS_ADMIN_PASSWORD` را در `.env` عوض کنید، همان‌ها برای ورود ادمین معتبرند.

### کاربران سایت و پنل هنرجو (`/login`)

جدول `users` — از `PlatformSeeder` و `RahbarHesabSeeder`:

| نقش | نام | ایمیل | موبایل | رمز |
|---|---|---|---|---|
| ادمین سایت | مدیر اصلی | `admin@rahbarhesab.ir` | `09120000000` | `secret123` |
| هنرجو نمونه | کاربر نمونه | `demo@example.com` | `09121111111` | `password` |
| مدرس نمونه | مدرس نمونه | `instructor@example.com` | — | `password` |
| مدرس محتوا | مرتضی رهبر | `instructor@rahbarhesab.com` | `09120000001` | `password` |

ورود سایت با **OTP موبایل** یا تب **ورود با رمز عبور** (ایمیل/موبایل + رمز بالا) ممکن است.

> توجه: ایمیل `admin@rahbarhesab.ir` هم در `cms_admins` و هم در `users` وجود دارد؛ رمزها فرق دارند. برای `/admin` رمز CMS، برای سایت رمز `secret123`.

### دادهٔ کمکی تست

| مورد | مقدار |
|---|---|
| کد تخفیف نمونه | `WELCOME10` (۱۰٪، حداقل سبد ۵۰۰٬۰۰۰ تومان) |
| دوره رایگان دمو | slug: `laravel-basics` (هنرجو `demo@example.com` ثبت‌نام شده) |

## مسیرهای پرکاربرد

| مسیر | توضیح |
|---|---|
| `/` | صفحه اصلی |
| `/courses` | آرشیو دوره‌ها |
| `/cart` · `/checkout` | سبد و تسویه |
| `/blog` | مقالات |
| `/login` | ورود / ثبت‌نام |
| `/panel` | پنل هنرجو |
| `/admin` | پنل مدیریت CMS |

## تم و فرانت

- تم فعال: `CMS_ACTIVE_THEME=rahbarhesab`
- ویوها و استایل‌ها: `themes/rahbarhesab/` و `public/themes/rahbarhesab/`
- Assetهای ادمین: Vuexy در `resources/assets` — بیلد با Vite

## تست و کیفیت

```sh
php artisan test
./vendor/bin/pint --dirty
```
