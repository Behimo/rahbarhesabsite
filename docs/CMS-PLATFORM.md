# Bisan CMS Platform

یک CMS/LMS مبتنی بر Laravel برای ساخت سایت‌های سفارشی — شبیه وردپرس با قابلیت افزونه و قالب.

## امکانات

- **پنل ادمین** (`/admin`) — صفحات، بلاگ، محصولات مارکتینگ، دوره‌های LMS، سفارش‌ها
- **پنل کاربری** (`/panel`) — دوره‌های من، سفارش‌ها، پروفایل
- **LMS** — دوره، فصل، درس، پیشرفت، ثبت‌نام
- **فروشگاه** — سبد، کوپن، سفارش، پرداخت زیبال/زرین‌پال (تومان در DB، ریال به درگاه)
- **اسپات‌پلیر** — صدور لایسنس با صف و retry
- **مهاجرت وردپرس** — `wp:migrate-users|courses|orders|audit` روی بکاپ `WP_DB_*`
- **قالب‌ها** — پوشه `themes/{name}/views`
- **افزونه‌ها** — پوشه `plugins/{Name}/` با `plugin.json` و Hook system

## نصب

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan cms:ensure-admin
```

## تنظیمات

| متغیر | توضیح |
|-------|--------|
| `CMS_ACTIVE_THEME` | قالب فعال (`rahbarhesab` برای راهبر حساب) |
| `CMS_PAYMENT_GATEWAY` | `zibal` (پیش‌فرض) یا `zarinpal` |
| `ZIBAL_MERCHANT` | مرچنت زیبال |
| `IPPANEL_API_KEY` | کلید API پنل پیامک |
| `IPPANEL_OTP_PATTERN` | کد پترن OTP در IPPanel |
| `ZARINPAL_MERCHANT_ID` | مرچنت زرین‌پال (اختیاری) |

## احراز هویت

ورود کاربران با **OTP موبایل** (ثبت‌نام خودکار) یا رمز عبور کاربران منتقل‌شده از وردپرس:
- `/login` → ارسال کد
- `/login/verify` → تأیید OTP
- `POST /login/password` → ایمیل/موبایل + رمز (ارتقای Phpass به Bcrypt)

## SEO

- Sitemap index: `/sitemap_index.xml` (مثل Rank Math)
- زیر sitemap: `post-sitemap.xml`, `page-sitemap.xml`, `course-sitemap.xml`, `product-sitemap.xml`
- Schema.org: Course, FAQ, Breadcrumb, Organization

## قالب راهبر حساب

```
themes/rahbarhesab/
  views/pages/home.blade.php
  views/pages/courses/
  public/themes/rahbarhesab/theme.css
```

برای لانچ `rahbarhesab.com`:
```bash
CMS_ACTIVE_THEME=rahbarhesab
APP_URL=https://rahbarhesab.com
php artisan migrate --seed
```

## ساخت قالب سفارشی

```
themes/my-theme/
  theme.json
  views/
    pages/home.blade.php   # override با namespace theme::
```

در `config/cms.php` مقدار `active_theme` را به `my-theme` تغییر دهید.

## ساخت افزونه

```
plugins/MyPlugin/
  plugin.json
  MyPluginServiceProvider.php
```

نمونه در `plugins/Example/`. از `App\Support\Hook` برای actions و filters استفاده کنید:

```php
Hook::addAction('order.fulfilled', fn ($order) => ...);
Hook::addFilter('cms.nav.links', fn (array $links) => $links);
```

## مسیرهای اصلی

| مسیر | توضیح |
|------|--------|
| `/courses` | لیست دوره‌ها |
| `/cart` | سبد خرید و اعمال کوپن |
| `/checkout` | تسویه و درگاه |
| `/login` | ورود OTP / رمز عبور |
| `/panel` | پنل کاربری (دوره، لایسنس، سفارش) |
| `/admin/courses` | مدیریت دوره‌های LMS |
| `/admin/orders` | سفارش، پرداخت دستی، لایسنس |
| `/admin/coupons` | کد تخفیف |
| `/admin/redirects` | ریدایرکت ۳۰۱ |

جزئیات خرید و مهاجرت وردپرس: `docs/SHOP-PAYMENTS-AND-MIGRATION.md`  
راهنمای برنامه‌نویس: `docs/HANDBOOK-DEVELOPER.md`

## حساب‌های نمونه (بعد از seed)

- کاربر: `demo@example.com` / `password`
- مدرس: `instructor@example.com` / `password`
- ادمین CMS: `admin@bisan.ir` / `password`
