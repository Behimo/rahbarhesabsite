# راهبر حساب (Rahbar Hesab)

پلتفرم آموزشی حسابداری و مالیات با CMS اختصاصی، فروش دوره، پرداخت آنلاین و پنل هنرجو.

**Stack:** Laravel 12 · PHP 8.3+ · Blade · Alpine.js · Vite · تم `themes/rahbarhesab`

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

## حساب ادمین و کاربران نمونه

احراز هویت پنل مدیریت و سایت از guard `web` و مدل `User` استفاده می‌کند؛ ادمین CMS جدول یا guard جداگانه ندارد. Seeder اصلی `DefaultUsersSeeder` است و نقش‌ها را از روی Role/Permission تنظیم می‌کند.

- ایمیل ادمین از `CMS_ADMIN_EMAIL` می‌آید (پیش‌فرض: `admin@rahbarhesab.ir`).
- رمز ادمین از `CMS_ADMIN_PASSWORD` می‌آید. در محیط production باید رمز حداقل ۱۲ کاراکتر باشد؛ در توسعهٔ محلی در صورت خالی‌بودن، Seeder مقدار پیش‌فرض توسعه‌ای `password` می‌گذارد.
- حساب‌های نمونه با رمزهای شناخته‌شده فقط برای local/dev هستند و در production ساخته نمی‌شوند.
- ورود کاربر سایت از OTP موبایل یا مسیر ورود با رمز کاربران منتقل‌شده انجام می‌شود؛ برای جزئیات و حساب‌های نمونهٔ فعلی، Seederها را مرجع قرار دهید.

**هشدار:** پس از اجرای seed در production، رمز قوی و منحصربه‌فرد تنظیم کنید و هرگز رمزهای نمونهٔ توسعه را در production استفاده نکنید.

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
