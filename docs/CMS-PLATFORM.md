# Bisan CMS Platform

یک CMS/LMS مبتنی بر Laravel برای ساخت سایت‌های سفارشی — شبیه وردپرس با قابلیت افزونه و قالب.

## امکانات

- **پنل ادمین** (`/admin`) — صفحات، بلاگ، محصولات مارکتینگ، دوره‌های LMS، سفارش‌ها
- **پنل کاربری** (`/panel`) — دوره‌های من، سفارش‌ها، پروفایل
- **LMS** — دوره، فصل، درس، پیشرفت، ثبت‌نام
- **فروشگاه** — سبد خرید، سفارش، پرداخت زرین‌پال
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
| `CMS_ACTIVE_THEME` | نام قالب فعال (پیش‌فرض: `default`) |
| `ZARINPAL_MERCHANT_ID` | مرچنت زرین‌پال |
| `ZARINPAL_SANDBOX` | `true` برای تست |

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
| `/cart` | سبد خرید |
| `/login` `/register` | احراز هویت کاربر |
| `/panel` | پنل کاربری |
| `/admin/courses` | مدیریت دوره‌های LMS |

## حساب‌های نمونه (بعد از seed)

- کاربر: `demo@example.com` / `password`
- مدرس: `instructor@example.com` / `password`
- ادمین CMS: `admin@bisan.ir` / `password`
