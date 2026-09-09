# راهنمای فنی پروژه — برای برنامه‌نویس تیم

> آخرین به‌روزرسانی: شهریور ۱۴۰۵  
> مخاطب: کسی که قراره روی کد کار کنه، نه کسی که فقط پنل رو می‌بینه.

---

## قبل از هر چیز

این پروژه اسمش توی composer «Bisan corporate website and CMS» هست، ولی عملاً برای **راهبر حساب** (`rahbarhesab.com`) ساخته شده: سایت آموزشی + فروش دوره + بلاگ + صفحات CMS.

اگه از وردپرس کار کرده باشی، ذهنیتش آشناست: قالب، افزونه، صفحه‌ساز، هوک. فقط به‌جای PHP خام وردپرس، Laravel 12 داریم.

**استک اصلی:**
- PHP 8.2+، Laravel 12
- دیتابیس: SQLite برای dev (MySQL برای production منطقی‌تره)
- پنل ادمین: Vuexy (Bootstrap 5) — `resources/views/layouts/vuexy/`
- سایت عمومی: Blade + Tailwind + Alpine — قالب فعال `themes/rahbarhesab/`
- Vite 7 برای assetها

اولین بار که clone می‌کنی:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan cms:ensure-admin
npm install && npm run build
php artisan serve
```

ادمین: `/admin/login` — ایمیل و پسورد از `.env` (`CMS_ADMIN_EMAIL` / `CMS_ADMIN_PASSWORD`).

---

## ساختار پوشه‌ها — فقط جاهایی که واقعاً می‌ری

```
app/
  Blocks/           ← کلاس‌های صفحه‌ساز
  Http/Controllers/
    Admin/          ← پنل مدیریت
    Site/           ← سایت عمومی
    Panel/          ← پنل کاربر (بعد از لاگین)
    Auth/           ← OTP موبایل
    Api/V1/         ← REST فقط خواندنی
  Models/           ← Eloquent
  Services/         ← منطق اصلی اینجاست، نه توی Controller
  Support/          ← Hook، Permission
config/cms.php      ← تنظیمات CMS
themes/rahbarhesab/ ← قالب فعال
plugins/            ← افزونه‌ها
resources/views/
  admin/            ← Blade ادمین
  blocks/           ← view رندر بلوک‌ها
routes/web.php      ← همه routeهای وب
```

**نکته:** Controllerها عمداً لاغر نگه داشته شدن. اگه داری منطق سنگین می‌نویسی توی Controller، احتمالاً جاش `app/Services/` هست.

---

## دو دنیا: ادمین CMS vs کاربر سایت

این رو قاطی نکن — گیر می‌کنی.

| | ادمین CMS | کاربر سایت |
|---|-----------|------------|
| Guard | `cms` | `web` |
| Model | `CmsAdmin` | `User` |
| ورود | ایمیل + رمز | فقط OTP موبایل |
| URL | `/admin/*` | `/login`, `/panel/*` |
| Middleware | `cms.admin`, `cms.permission` | `auth` |

ادمین‌ها توی جدول `cms_admins` هستن. کاربران عادی توی `users`. این دو تا به هم ربط ندارن.

کاربر سایت با شماره موبایل ثبت‌نام/ورود می‌کنه (`Auth\AuthController`). OTP از IPPanel می‌ره (`config/otp.php`). اگه `IPPANEL_API_KEY` خالی باشه، کد توی log می‌افته — برای dev خوبه، production نه.

---

## پنل ادمین — نقشه routeها

همه زیر `/admin`، با middleware `cms.admin` + `cms.permission`.

| بخش | Route | Controller |
|-----|-------|------------|
| داشبورد | `/admin` | `DashboardController` |
| صفحات | `/admin/pages` | `PageController` |
| صفحه‌ساز | `/admin/pages/{page}/builder` | `PageBuilderController` |
| بلاگ | `/admin/posts` | `PostController` |
| دوره‌ها | `/admin/courses` | `CourseController` |
| سفارش‌ها | `/admin/orders` | `OrderController` |
| منوها | `/admin/menus` | `MenuController` |
| قالب‌ها | `/admin/themes` | `ThemeController` |
| افزونه‌ها | `/admin/plugins` | `PluginController` |
| تنظیمات | `/admin/settings` | `SettingController` |
| رسانه | `/admin/media` | `MediaController` |

**نقش‌ها و دسترسی:** `App\Support\Permission`

- `admin` — همه چیز
- `editor` — صفحات، بلاگ، منو، رسانه
- `shop_manager` — سفارش + دوره
- `instructor` — دوره + رسانه

نقشه route → permission توی `Permission::routeMap()` هست. middleware `EnsureAdminPermission` چک می‌کنه.

---

## سایت عمومی — routeهای مهم

فایل: `routes/web.php`

```
/                    → HomeController
/courses             → لیست دوره
/courses/{slug}      → صفحه دوره
/courses/{slug}/learn/{lesson?}  → پلیر (نیاز به auth)
/blog                → بلاگ
/p/{slug}            → صفحات CMS
/cart, /checkout/*   → فروشگاه
/login               → OTP
/panel/*             → پنل کاربر
```

**ریدایرکت‌های ثابت:**
- `/products` → `/courses` (301)
- `/why-bisan`, `/services` → `/about`

**ریدایرکت دیتابیسی:** `HandleCmsRedirects` middleware — جدول `cms_redirects`.

---

## مدل‌های اصلی — بدون حفظ کردن، بدون

### CMS
- `CmsPage` — صفحات + `builder_content` (JSON)
- `CmsPost` / `CmsCategory` — بلاگ
- `CmsSetting` — key/value (لوگو، عنوان سایت، ...)
- `CmsMenu` / `CmsMenuItem` — منوی سایت
- `CmsTheme` / `CmsPlugin` — قالب و افزونه فعال
- `CmsRedirect` — ریدایرکت 301/302

### فروش و LMS
- `ShopProduct` — محصول قابل فروش (دوره، فایل، ...)
- `Course` → `CourseSection` → `CourseLesson` — ساختار دوره
- `CourseEnrollment` — ثبت‌نام کاربر
- `LessonProgress` — پیشرفت درس
- `Order` / `OrderItem` / `Payment` — سفارش
- `SpotplayerLicense` — لایسنس DRM

رابطه: هر دوره فروشی یک `ShopProduct` داره که `type=course` و یک `Course` وصل‌شده.

---

## صفحه‌ساز — قلب CMS

### ذخیره‌سازی

توی `CmsPage.builder_content`:

```json
{
  "blocks": [
    {
      "type": "hero",
      "settings": {
        "title": "...",
        "subtitle": "...",
        "cta_text": "...",
        "cta_url": "..."
      }
    }
  ]
}
```

صفحه‌ای که `builder_enabled=true` باشه، محتواش از همین JSON رندر می‌شه نه از `content` HTML.

### کلاس‌های مرتبط

| کلاس | کار |
|------|-----|
| `BlockInterface` | قرارداد هر بلوک |
| `AbstractBlock` | پایه + `defaultSettings()` |
| `BlockRegistry` | ثبت و resolve بلوک‌ها |
| `BlockRenderer` | JSON → HTML |
| `PageBuilderController` | UI ادمین، save، preview |

### بلوک‌های پیش‌فرض

همه توی `app/Blocks/`:

`HeroBlock`, `TextBlock`, `HtmlBlock`, `ImageBlock`, `VideoBlock`, `CtaBlock`, `ColumnsBlock`, `FaqBlock`, `StatsBlock`, `TestimonialsBlock`, `PostsBlock`, `ProductsBlock`, `CoursesBlock`

view هر کدوم: `resources/views/blocks/{type}.blade.php`

### بلوک جدید اضافه کنی

۱. کلاس بساز:

```php
namespace App\Blocks;

class PricingBlock extends AbstractBlock
{
    public function type(): string { return 'pricing'; }
    public function label(): string { return 'جدول قیمت'; }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => ''],
            'plans' => [
                'type' => 'repeater',
                'label' => 'پلن‌ها',
                'default' => [],
                'fields' => [
                    'name' => ['type' => 'text', 'label' => 'نام', 'default' => ''],
                    'price' => ['type' => 'text', 'label' => 'قیمت', 'default' => ''],
                ],
            ],
        ];
    }

    public function render(array $settings): string
    {
        return $this->view('blocks.pricing', [
            'title' => $settings['title'] ?? '',
            'plans' => $settings['plans'] ?? [],
        ]);
    }
}
```

۲. view بساز: `resources/views/blocks/pricing.blade.php`

۳. ثبت کن — یکی از این دو:

```php
// config/cms.php
'blocks' => [
    \App\Blocks\PricingBlock::class,
],
```

یا توی افزونه:

```php
Hook::addFilter('cms.blocks.classes', fn ($classes) => [...$classes, PricingBlock::class]);
```

**typeهای schema** که UI صفحه‌ساز می‌شناسه: `text`, `textarea`, `richtext`, `number`, `select`, `image`, `code`, `repeater`. برای `columns` UI جدا داریم (`layout`).

### چیدمان ستونی — ساختار تو در تو

`ColumnsBlock` (`type: columns`) این شکلیه:

```
ستون ۱، ۲، ۳... (داینامیک)
  └── ۴ قسمت ثابت
        └── چند بلوک (text, image, faq, ...)
```

JSON:

```json
{
  "type": "columns",
  "settings": {
    "columns": [
      {
        "sections": [
          { "blocks": [{ "type": "text", "settings": { "content": "..." } }] },
          { "blocks": [] },
          { "blocks": [] },
          { "blocks": [] }
        ]
      }
    ]
  }
}
```

- `ColumnsBlock::SECTIONS_PER_COLUMN = 4` — ثابته، عوضش نکن مگر UI رو هم عوض کنی.
- `columns` داخل section تو در تو نمی‌شه (توی builder فیلتر شده).
- `BlockRegistry::flattenBlocks()` برای جمع assetهای nested لازمه.
- فرمت قدیمی `column.content` هنوز migrate می‌شه به text block توی section 0.

UI صفحه‌ساز: `resources/views/admin/pages/builder.blade.php` — JS سمت کلاینت، schema-driven.

---

## قالب‌ها (Themes)

پوشه: `themes/{slug}/`

```
themes/rahbarhesab/
  theme.json          ← manifest
  views/
    layouts/site.blade.php
    pages/home.blade.php
    pages/courses/...
  public/             ← کپی می‌شه به public/themes/rahbarhesab/
```

**فعال‌سازی:** DB (`cms_themes.is_active`) یا fallback `CMS_ACTIVE_THEME` در `.env`.

**رندر view:** `ThemeService::view('pages.home', $data)` — اول `theme::` رو امتحان می‌کنه، بعد view پیش‌فرض app.

**پیش‌نمایش:** `/?preview_theme=slug`

**کشف:** `php artisan cms:discover`

قالب ZIP از `/admin/themes` آپلود می‌شه.

---

## افزونه‌ها و Hook

ساختار: `plugins/MyPlugin/plugin.json` + ServiceProvider

PSR-4: `Plugins\` — توی `composer.json` autoload شده.

نمونه: `plugins/Example/`

**Hook API** (`App\Support\Hook`) — شبیه وردپرس:

```php
Hook::addAction('order.fulfilled', function ($order) { ... });
Hook::addFilter('cms.nav.links', fn ($links) => $links);
Hook::addFilter('cms.blocks.classes', fn ($c) => [...$c, MyBlock::class]);
Hook::addAction('cms.blocks.boot', fn ($registry) => $registry->register(...));
```

**Actionهای مهم:**
- `order.created`, `order.item.fulfilled`, `order.fulfilled`
- `course.enrolled`

**Filterهای مهم:**
- `cms.nav.links` — لینک‌های nav
- `cms.blocks.classes` — لیست کلاس بلوک

`AppServiceProvider::boot()` → `PluginService::bootActive()` افزونه‌های فعال رو boot می‌کنه.

---

## LMS و دوره‌ها

**Admin:** `Admin\CourseController` — CRUD دوره، فصل، درس.

**سایت:** `Site\CourseController` — لیست، جزئیات، learn، preview، complete.

**ثبت‌نام:**
- رایگان: `/courses/{slug}/enroll-free`
- پولی: checkout → `OrderService::fulfill()` → enrollment

**ویدیو:** provider توی lesson — `aparat`, `youtube`, `vimeo`, `upload`, `spotplayer`, `download`

**SpotPlayer:** `SpotPlayerService` + `IssueSpotplayerLicenseJob` (queue). API key: `SPOTPLAYER_API_KEY`.

**پیشرفت:** `LessonProgress` — POST `/courses/{slug}/lessons/{lessonSlug}/complete`

**پیش‌نمایش رایگان:** `is_free_preview` روی lesson.

---

## فروشگاه و پرداخت

**Gateway پیش‌فرض:** Zibal (`CMS_PAYMENT_GATEWAY=zibal`)

**جایگزین:** Zarinpal — `ZARINPAL_MERCHANT_ID`, `ZARINPAL_SANDBOX`

**Flow:**
1. `/cart` — `CartService` (session/guest)
2. `/checkout` — نیاز به login
3. redirect به درگاه
4. `/checkout/callback` — verify
5. `OrderService::fulfill()` — enrollment + SpotPlayer

**قیمت:** integer (ریال/تومان — یکدست نگه دار)، `effectivePrice()` = sale_price ?? price

---

## API

`routes/api.php` — فقط GET، read-only.

Header: `Authorization: Bearer {CMS_API_TOKEN}`

```
GET /api/v1/pages
GET /api/v1/pages/{slug}
GET /api/v1/posts
GET /api/v1/posts/{slug}
GET /api/v1/courses
GET /api/v1/courses/{slug}
```

Middleware: `api.token` (`EnsureApiToken`)

---

## Artisan — دستوراتی که واقعاً لازم داری

| دستور | کار |
|-------|-----|
| `cms:discover` | کشف قالب، افزونه، taxonomy پیش‌فرض |
| `cms:ensure-admin` | ساخت/آپدیت ادمین از `.env` |
| `cms:publish-scheduled` | انتشار زمان‌بندی‌شده — **هر دقیقه scheduler** |
| `cms:refresh-brand` | مهاجرت برندینگ قدیمی Bisan → Rahbar |

**Scheduler** (`routes/console.php`):

```bash
# dev
php artisan schedule:work

# production cron
* * * * * cd /path && php artisan schedule:run
```

**Queue** (SpotPlayer و کارهای سنگین):

```bash
php artisan queue:work
```

---

## `.env` — متغیرهایی که معمولاً فراموش می‌شن

```env
CMS_ACTIVE_THEME=rahbarhesab
CMS_ADMIN_EMAIL=admin@rahbarhesab.ir
CMS_ADMIN_PASSWORD=...
CMS_API_TOKEN=...

CMS_PAYMENT_GATEWAY=zibal
ZIBAL_MERCHANT=...

IPPANEL_API_KEY=...
IPPANEL_OTP_PATTERN=...

SPOTPLAYER_API_KEY=...
```

بقیه توی `config/cms.php` و `config/otp.php` map شدن.

---

## Seederها — بعد migrate --seed چی داری

`DatabaseSeeder` به ترتیب:
1. `CmsSeeder` — ادمین، صفحات سیستمی، پست نمونه
2. `PlatformSeeder` — کاربر demo
3. `RahbarHesabSeeder` — محتوای راهبر، دوره‌ها
4. `CmsExtensionsSeeder` — قالب، منو، taxonomy

**اکانت‌های تست:**
- ادمین: از `.env`
- `demo@example.com` / `password`
- `instructor@example.com` / `password`

---

## SEO

- Sitemap index: `/sitemap_index.xml`
- زیر sitemap: `post-sitemap.xml`, `page-sitemap.xml`, ...
- `SeoService` — Schema.org (Course, FAQ, Breadcrumb, ...)
- `robots.txt` — dynamic

---

## Deploy — چیزهایی که production گیر می‌ده

1. `APP_ENV=production`, `APP_DEBUG=false`
2. `npm run build` — assetها commit نشدن، build لازمه
3. `php artisan migrate --force`
4. Scheduler + Queue worker
5. HTTPS — `AppServiceProvider` forceScheme در production
6. IPPanel و Zibal واقعی

CI موجود: `.github/workflows/deploy.yaml` (Chabokan)

Health: `/up`

---

## جاهایی که معمولاً باگ می‌خوریم

**۱. صفحه builder ذخیره نمی‌شه**  
فرم JSON رو توی hidden input می‌فرسته. CSRF و validation رو چک کن. `builder_content` باید array parse بشه.

**۲. قالب عوض شد ولی view قدیمی میاد**  
کش view: `php artisan view:clear`. `ThemeService` namespace `theme::` رو درست register کرده؟

**۳. OTP نمی‌ره**  
`IPPANEL_API_KEY` خالیه → log. pattern code درسته؟

**۴. دوره بعد خرید enroll نمی‌شه**  
callback پرداخت، `OrderService::fulfill()`, queue worker.

**۵. بلوک nested توی columns رندر نمی‌شه**  
`ColumnsBlock::render()` از `BlockRegistry` استفاده می‌کنه. type نامعتبر silent fail می‌کنه (try/catch).

**۶. دسترسی ادمین**  
role توی `cms_admins` — `Permission::roleDefaults()`.

---

## فایل‌های مرجع سریع

| موضوع | فایل |
|-------|------|
| Route وب | `routes/web.php` |
| Route API | `routes/api.php` |
| Config CMS | `config/cms.php` |
| ثبت بلوک | `app/Services/BlockRegistry.php` |
| صفحه‌ساز UI | `resources/views/admin/pages/builder.blade.php` |
| Hook | `app/Support/Hook.php` |
| سفارش | `app/Services/OrderService.php` |
| قالب | `app/Services/ThemeService.php` |
| منوی ادمین | `resources/menu/verticalMenu.json` |

---

## جمع‌بندی برای کسی که تازه اومده

۱. Laravel معمولیه — MVC + Service layer.  
۲. CMS شبیه WP: theme, plugin, block, hook.  
۳. دو auth جدا: cms admin vs user OTP.  
۴. LMS روی ShopProduct + Course سوار شده.  
۵. صفحه‌ساز JSON توی `builder_content` — ColumnsBlock nested داره.  
۶. قبل deploy: build, migrate, scheduler, queue, env.

سؤال داشتی اول `routes/web.php` و `config/cms.php` رو باز کن. نصف جواب‌ها اونجاست.

— بهنام
