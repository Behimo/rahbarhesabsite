# بازبینی فنی پیاده‌سازی سئو (از دید برنامه‌نویسی و ساختار پروژه)

تاریخ بازبینی: ۲۰۲۶-۱۰-۲۶
محدوده: همه‌ی کدهایی که برای سئو و هم‌خوانی URL با وردپرس اضافه شده‌اند:
`SeoService`، `PermalinkService`، `App\Routing\UrlGenerator`، `SitemapController`، میدل‌ورهای سئو،
`config/seo.php`، `public/.htaccess`، `layouts/site.blade.php`، کنترلرهای سایت و فرم‌های ادمین.

این سند درباره‌ی «درست بودن تصمیم سئویی» نیست؛ درباره‌ی این است که **کدام پیاده‌سازی‌ها از نظر مهندسی نرم‌افزار،
ساختار پروژه یا رفتار واقعی کد مشکل دارند**. موارد بر اساس شدت مرتب شده‌اند.

---

## وضعیت پس از صحت‌سنجی و اصلاح — ۲۰۲۶-۱۰-۲۶

**متن بندهای پایین، گزارش اولیه است؛ جدول زیر وضعیت فعلی هر ۲۲ بند را مشخص می‌کند.** شمارش شدت‌ها در خلاصهٔ اولیه، شمارش ایرادهای باز امروز نیست. ادعاهای اثبات‌نشده و پیشنهادهای ناسازگار با قرارداد URL نیز در همین جدول تصحیح شده‌اند.

| بند | نتیجهٔ صحت‌سنجی و اقدام نهایی | شاهد اصلی |
|---|---|---|
| ۱-۱ | خطر قواعد Apache از روی کد تأیید شد؛ قواعد HTTPS/www/slash از `.htaccess` حذف و به middleware فقط GET/HEAD عمومی منتقل شد. مثال API login در این پروژه وجود ندارد؛ ادعای اجرای واقعی Apache تأیید نشد. | `SeoUrlPolicyTest`؛ بررسی `.htaccess` |
| ۱-۲ | اصلاح شد: سیاست مشترک `PermalinkService`/`config/seo.php`؛ نرمال‌سازی production در یک پرش و بدون اجبار دامنهٔ production روی لوکال. | تست دامنه، scheme، slash، query فارسی و trusted proxy |
| ۲-۱ | اصلاح شد: شمارنده‌ها بدون timestamp؛ بازدید، ویرایش محتوا نیست. تاریخ پایگاه داده، Article و sitemap ثابت می‌ماند. همین حفاظت برای قوانین و شمارندهٔ کافه‌سؤال اعمال شد. | `SeoMetadataTest`؛ `SitemapCacheAndPaginationTest` |
| ۲-۲ | اصلاح شد: ردیف فعال دستی/Rank Math، query اختصاصی، `/?p=`، مقصد HTTP(S)، ۴۱۰، جلوگیری از حلقه و کوتاه‌سازی زنجیره اجرا می‌شوند؛ aliasهای www/apex داخلی‌اند. مسیر عمومی دوره به `/courses/` منتقل نمی‌شود. | `SeoUrlPolicyTest`؛ `SitemapAndRedirectTest` |
| ۲-۳ | اصلاح شد: noindex/nofollow در ترکیب سیاست درخواست و محتوا غالب‌اند؛ staging همیشه noindex/nofollow. | `SeoMetadataTest`؛ preview ادمین در `SeoValidationAndMigrationTest` |
| ۲-۴ | اصلاح شد: مقادیر HomeController به پیش‌فرض منتقل شدند؛ متای CMS خانه و canonical سفارشی حفظ می‌شوند. | تست متای ادمین خانه و فقط یک query صفحهٔ home |
| ۲-۵ | اصلاح شد: `seo` فقط override خام؛ `seoMeta` فقط در composer موجود `layouts.site` ساخته می‌شود. صفحهٔ واکشی‌شده مجدداً query نمی‌شود. | `SeoMetadataTest`؛ `ViewServiceProvider` |
| ۳-۱ | اصلاح شد: تشخیص فایل/سیستم و اسلش در سیاست مشترک؛ `system_prefixes` عمداً با `reserved_prefixes` یکی نیست. | `PermalinkService`؛ `SeoUrlPolicyTest` |
| ۳-۲ | پیشنهاد `formatPathUsing()` رد شد: Laravel نصب‌شده بعد از callback اسلش را trim می‌کند. override حداقلی با فراخوانی `parent::format()` حفظ و کپی پیاده‌سازی parent حذف شد. | `App/Routing/UrlGenerator`؛ تست‌های URL parity |
| ۳-۳ | اصلاح شد: catch-allها آخر فایل routes قرار گرفتند. محافظ نام‌های رزروشده برای جلوگیری از تصاحب slugهای سیستم حفظ شد. | `routes/web.php`؛ `AdminSlugValidationTest`؛ `LaunchContractsTest` |
| ۳-۴ | کش کوتاه و namespaced، واکشی دسته‌ای با `lazyById(250)`، ستون‌های منتخب، invalidation مدل و sync taxonomy و حذف آرشیوهای خالی پیاده شد. **ادعای N+1 رد شد**: accessor از رابطهٔ eager-loaded استفاده می‌کرد. والد دستهٔ دارای محتوای زیرشاخه حفظ می‌شود. | `SitemapCacheAndPaginationTest`؛ بررسی `HasCategories` |
| ۳-۵ | اصلاح شد: همهٔ URLهای sitemap در `SitemapService` با `PermalinkService` ساخته می‌شوند؛ کنترلر دیگر loc را parse و بازسازی نمی‌کند. | `SitemapService`؛ تست‌های sitemap |
| ۳-۶ | متدهای مرده، Contact index تکراری و if خالی حذف شدند؛ compatibility endpoint محصول به دوره‌های واقعی متصل شد. `services/why-bisan` از قبل از sitemap مستثنا بودند و فقط رزرو ماندند؛ کامنت نادرست `p` اصلاح شد. | تست compatibility sitemap؛ `config/seo.php` |
| ۳-۷ | تغییر سازگاری انجام شد: `/sitemap.xml` اکنون index کامل با ۲۰۰ است. خروجی قبلی الزاماً XML نامعتبر نبود؛ index اصلی در robots معرفی شده بود. | `SitemapCacheAndPaginationTest`؛ مرورگر: هر دو index با ۲۰۰ |
| ۳-۸ | اصلاح شد: Rule/normalizer و component مشترک؛ PostRequest واقعاً به CRUD متصل و با جداول categories/tags سازگار شد. انتخاب گستردهٔ واردشده حفظ می‌شود؛ enum چهارگزینه‌ایِ محدودکننده اعمال نشد. | `SeoValidationAndMigrationTest`؛ `SeoMetadataTest` |
| ۳-۹ | اصلاح شد: مالکیت ستون‌ها قطعی؛ حذف unique پیش از ستون؛ حفظ robots صفحه متعلق به migration پایه؛ rollback/reapply به ترتیب معکوس با migration composite فقط در SQLite حافظه‌ای. | `SeoValidationAndMigrationTest` |
| ۴-۱ | اصلاح شد: ساخت متا، sidebar و route flags از Blade به composer موجود با تزریق سرویس منتقل شد؛ shared defaults دادهٔ خاص کنترلر را بازنویسی نمی‌کنند. | `ViewServiceProvider`؛ تست‌های رندر و مرورگر |
| ۴-۲ | اصلاح شد: og_type قابل override و برای نوشته/قانون article؛ hreflang تک‌زبانهٔ زائد حذف شد. دوره همچنان website همراه schema تخصصی Course است. | `SeoMetadataTest`؛ DOM نوشته در مرورگر |
| ۴-۳ | اصلاح شد: absoluteUrl عمومی و Article schema در SeoService؛ ساخت متای کامل برای تصویر حذف شد. | `SeoMetadataTest`؛ `SeoService::articleSchema` |
| ۴-۴ | اصلاح شد: Request تزریق می‌شود؛ استفاده از request سراسری در منطق متای SeoService حذف و imports مرتب شدند. نام routeهای سیاست robots همچنان صریح و عمدی در همان سرویس است. | `SeoService`؛ تست‌های سیاست درخواست |
| ۴-۵ | اصلاح شد: defaults سئو/پسوند بلاگ/برچسب خانه مرکزی؛ Money برای تومان→IRR؛ مدت ساختگی حذف شد. InStock برای دورهٔ دیجیتال فروختنی حفظ شد، نه به‌عنوان ادعای موجودی فیزیکی. | `SeoMetadataTest`؛ `SeoService::courseSchema` |
| ۴-۶ | اصلاح شد: صفحهٔ خارج از محدودهٔ بلاگ/دوره/آرشیو ۴۰۴، صفحهٔ اول خالی ۲۰۰ و query فیلتر در pagination حفظ می‌شود. | `SitemapCacheAndPaginationTest`؛ مرورگر: page/999 برابر ۴۰۴ |

### شواهد اجرای نهایی

اجرای نهایی پس از آخرین اصلاح URL و Pint:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync php vendor/bin/phpunit --do-not-cache-result
```

**نتیجه: ۴۱۵ تست، ۱۹۸۷ assertion، همگی موفق** (PHP 8.3.30، PHPUnit 11.5.55، حدود ۶۶ ثانیه). سوئیت پیش‌فرض غیر MySQL اجرا شد؛ تست و rollback/reapply هیچ اتصال واقعی دیتابیس را هدف نگرفتند. Pint فقط روی PHPهای تغییرکرده/جدید اجرا شد.

فایل‌های regression جدید:

- [`SeoUrlPolicyTest.php`](../../tests/Feature/SeoUrlPolicyTest.php): متدها/سیستم/فایل، normalization، فارسی، origin flags، trusted proxy، chain/loop/query/410/unsafe target، cache و WP ID.
- [`SeoMetadataTest.php`](../../tests/Feature/SeoMetadataTest.php): تاریخ ثابت، متای خانه و query یکتا، robots و مدت واقعی.
- [`SitemapCacheAndPaginationTest.php`](../../tests/Feature/SitemapCacheAndPaginationTest.php): کش و invalidation، taxonomy و ancestors، namespace، compatibility endpoints و ۴۰۴.
- [`SeoValidationAndMigrationTest.php`](../../tests/Feature/SeoValidationAndMigrationTest.php): CRUD واقعی ادمین در حافظه، نامک فارسی/دسته/تگ، Rule و گزینهٔ واردشدهٔ robots، preview noindex و rollback/reapply.

اجرای برنامه با preview محلی (پورت ۸۰۰۱ چون ۸۰۰۰ مشغول بود) و بررسی DOM/network:

- خانه و بلاگ: ۲۰۰، canonical روی همان host:port، `noindex, nofollow` لوکال؛ ساختار و عنوان صفحه رندر شد.
- `/accounting-guides/tax-declaration-guide/`: ۲۰۰، canonical همان path، `og:type=article` و JSON-LD Article با dateModified واقعی.
- `/product/its-course/`: ۲۰۰، بدون تغییر به `/courses/`؛ canonical همان محصول و Course با IRR و مدت ذخیره‌شده، نه مدت پیش‌فرض ساختگی. در viewport موبایل ۳۷۵ پیکسلی، عرض سند و main هر دو ۳۷۵ و بدون overflow افقی بود؛ screenshot ثبت و viewport پس از بررسی به desktop بازگردانده شد.
- `/sitemap_index.xml` و `/sitemap.xml`: ۲۰۰ و `sitemapindex`؛ `/product-sitemap.xml`: ۲۰۰ و URL دوره‌های واقعی.
- `/robots.txt`: ۲۰۰ و `Disallow: /` در لوکال؛ page/999 بلاگ و دوره: ۴۰۴.
- سرور خطای اجرایی گزارش نکرد. چند تصویر seed/محتوای واردشدهٔ موجود در لوکال ۴۰۴ دارند؛ این خروجی به‌عنوان «همهٔ منابع سالم» گزارش نمی‌شود. فایل تصویر در این کار وارد/جایگزین نشد.
- فرم ادمین در مرورگر به ورود نیاز داشت؛ credential ساخته نشد و فرم نوشتنی ارسال نشد. صحت رندر کنترل مشترک و اعتبارسنجی هر سه فرم با تست حافظه‌ای تأیید شد، نه نشست مرورگری authenticated.

### حدود تضمین و استقرار

- هیچ migrate/rollback روی دیتابیس واقعی، انتقال دادهٔ وردپرس، commit یا push در این اصلاحات انجام نشده است. انتقال زندهٔ وردپرس همچنان آخرین فاز و مشروط به backup/staging و `LAUNCH-GATE.md` است.
- migration جدید `2026_10_26_120000_expand_seo_robots_columns.php` برای ظرفیت ۵۰۰ کاراکتری robots آماده شده و فقط در SQLite حافظه‌ای اجرا شده است. استقرار واقعی باید با پشتیبان و روال migration پروژه انجام شود؛ rollback کاهندهٔ طول روی دادهٔ بلند بدون بررسی امن نیست. migration قدیمی را روی دیتابیس موجود بازاجرا یا rollback نکنید.
- robots معتبر پشتیبانی‌شده: index/noindex، follow/nofollow، all/none، noarchive، nosnippet، noimageindex، notranslate، indexifembedded، nocache و `max-snippet`/`max-video-preview`/`max-image-preview`. ادعای پشتیبانی همهٔ directiveهای ممکن یا `unavailable_after` نمی‌شود.
- کش sitemap برابر ۳۰۰ ثانیه است؛ مدل/حذف/بازیابی و syncهای رسمی دسته/تگ آن را invalidate می‌کنند. عملیات bulk و تغییرات pivot مستقیم باید `CacheService::flushContent()` را فراخوانی کنند؛ تغییرات زمان‌محور بدون event حداکثر با TTL تازه می‌شوند. خروجی آرایه/XML هنوز متناسب با اندازهٔ سند حافظه مصرف می‌کند؛ streaming کامل نیست.
- آزمون Apache واقعی و موتور MySQL انجام نشده‌اند. PHP built-in رفتار `.htaccess` را اثبات نمی‌کند؛ trusted proxy باید متناسب با زیرساخت واقعی تنظیم شود.
- بازبینی مستقل به خطای API 429 متوقف شد و نتیجه‌ای به‌عنوان تأیید مستقل ندارد؛ بررسی نهایی مستقیم، تست‌های regression و اجرای برنامه انجام شد.

---

## خلاصهٔ گزارش اولیه (پیش از اصلاح)

| شدت | تعداد | مهم‌ترین مورد |
|---|---|---|
| بحرانی | ۲ | ریدایرکت ۳۰۱ روی درخواست‌های POST ادمین و API در `.htaccess` |
| بالا | ۵ | به‌روز شدن `updated_at` با هر بازدید پست؛ ریدایرکت‌های ذخیره‌شده‌ای که هرگز اجرا نمی‌شوند |
| متوسط | ۹ | منطق تکراری، کد مرده، مقادیر هاردکد |
| پایین | ۶ | ناسازگاری‌های جزئی و تمیزی کد |

---

## ۱. بحرانی

### ۱-۱. قانون «اسلش انتهایی» در `.htaccess` درخواست‌های POST/PUT/DELETE را خراب می‌کند

فایل: `public/.htaccess`

```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !/$
RewriteCond %{REQUEST_URI} !\.[a-zA-Z0-9]{1,8}$
RewriteRule ^(.*)$ /$1/ [L,R=301]
```

این قانون **همه‌ی متدها و همه‌ی مسیرها** را شامل می‌شود. از طرف دیگر `App\Routing\UrlGenerator` عمداً برای
مسیرهای `admin.*`، `/api` و `/panel` اسلش اضافه نمی‌کند. نتیجه روی Laragon (Apache) تست و تأیید شد:

```
POST /admin/login          → 301 → /admin/login/
POST /api/v1/auth/login    → 301 → /api/v1/auth/login/
```

مرورگر بعد از ۳۰۱، درخواست POST را به GET تبدیل می‌کند و بدنه‌ی فرم از بین می‌رود. یعنی ورود ادمین، ذخیره‌ی
فرم‌های ادمین (`/admin/posts`، `/admin/pages/{id}` با PUT و ...)، و همه‌ی کلاینت‌های API پشت Apache از کار می‌افتند.

ریشه‌ی ساختاری: قانون اسلش در **دو جای مستقل** تعریف شده (`.htaccess` و `UrlGenerator`) و لیست استثناهایشان با هم
هماهنگ نیست.

پیشنهاد:
- این قانون فقط روی `GET`/`HEAD` اعمال شود (`RewriteCond %{REQUEST_METHOD} ^(GET|HEAD)$`).
- مسیرهای `admin`، `api`، `panel`، `livewire`، `sanctum`، `up` استثنا شوند.
- بهتر: نرمال‌سازی URL به یک میدل‌ور Laravel منتقل شود تا هم تست‌پذیر باشد و هم با Nginx کار کند (بند ۱-۲).

### ۱-۲. نرمال‌سازی URL در سه جای متناقض تعریف شده است

| محل | چه می‌گوید |
|---|---|
| `NormalizeSeoUrl` | «عمداً ریدایرکت نمی‌کند؛ قرارداد سئو ۲۰۰ روی مسیر است، نه ۳۰۱» — و هیچ کاری نمی‌کند |
| `public/.htaccess` | HTTPS، حذف www و اسلش انتهایی را با ۳۰۱ اجباری می‌کند |
| `config/seo.php` | کلیدهای `force_https`، `force_www`، `trailing_slash` تعریف شده ولی `force_https` و `force_www` هیچ‌جا خوانده نمی‌شوند |

مشکلات:
- مستندات کد (DocBlock میدل‌ور و کامنت `UrlGenerator` که به «۳۰۱ میدل‌ور NormalizeSeoUrl» اشاره می‌کند) با رفتار واقعی
  سیستم مغایر است.
- رفتار سئو به وب‌سرور وابسته شده: اگر سرور production روی Nginx باشد، هیچ‌کدام از این ریدایرکت‌ها اجرا نمی‌شوند و
  نسخه‌های `http`/`www`/بدون اسلش همه ۲۰۰ برمی‌گردانند (محتوای تکراری).
- تست‌های PHPUnit اصلاً این رفتار را نمی‌بینند، چون `.htaccess` در تست اجرا نمی‌شود.

پیشنهاد: یک منبع حقیقت. یا منطق در `NormalizeSeoUrl` (فقط برای GET، با خواندن `config('seo.*')`) پیاده شود و
`.htaccess` به حالت پیش‌فرض Laravel برگردد، یا میدل‌ور و کلیدهای بلااستفاده‌ی کانفیگ حذف شوند و قانون سرور مستند شود.

---

## ۲. بالا

### ۲-۱. هر بازدید پست، `updated_at` را تغییر می‌دهد

فایل: `app/Http/Controllers/Site/BlogController.php`

```php
$post->increment('views');
```

در Eloquent، متد `increment` ستون `updated_at` را هم به‌روز می‌کند (کوئری واقعی بررسی شد:
`update cms_posts set views = views + 1, updated_at = ? ...`). پیامدها:
- `lastmod` در `post-sitemap.xml` با هر بازدید (حتی بازدید ربات گوگل) عوض می‌شود و برای گوگل بی‌اعتبار می‌شود.
- `dateModified` در اسکیمای `Article` هم دروغ می‌شود.
- هر درخواست GET یک UPDATE روی دیتابیس می‌زند.

پیشنهاد: `CmsPost::withoutTimestamps(fn () => $post->increment('views'))` یا
`$post->newQuery()->whereKey($post->id)->toBase()->increment('views')`، و در درازمدت شمارش بازدید در صف/کش.

### ۲-۲. جدول ریدایرکت‌ها ذخیره می‌شود، ولی هیچ‌وقت اجرا نمی‌شود

فایل‌ها: `HandleCmsRedirects`، `Admin\RedirectController`، `RankMathRedirectImporter`، `ImportRankMathRedirectsCommand`

- `HandleCmsRedirects::handle()` فقط `$next($request)` برمی‌گرداند، ولی به‌صورت **گلوبال** ثبت شده.
- در عین حال یک UI کامل ادمین، دستور import از Rank Math، و منطق پاک‌کردن کش (`cacheKey`) برای همین جدول وجود دارد.
- تست‌ها صریحاً تأیید می‌کنند که ریدایرکت‌ها اجرا نشوند.

از نظر ساختار: یک قابلیت کامل با رابط کاربری وجود دارد که اثری ندارد؛ ادمین ریدایرکت ثبت می‌کند و انتظار دارد کار کند.
از نظر سئو: ریدایرکت‌هایی که الان روی وردپرس زنده با ۳۰۱ کار می‌کنند، بعد از مهاجرت ۴۰۴ می‌شوند.

پیشنهاد: یا اجرای ریدایرکت (حداقل برای ردیف‌های `source = rankmath`) فعال شود، یا UI ادمین به‌وضوح «فقط آرشیو» باشد
و میدل‌ور خالی و منطق کش حذف شوند.

### ۲-۳. `robots` ذخیره‌شده در محتوا، منطق `noindex` خودکار را از کار می‌اندازد

فایل: `SeoService::meta()` و `defaultRobots()`

`defaultRobots()` برای URLهای دارای `utm_*`، `ref`، `s`، `replytocom` مقدار `noindex, follow` می‌سازد، اما در `meta()`
مقدار override همیشه برنده است. از آن‌جا که:
- فرم ادمین (پست، صفحه، دوره) همیشه یک مقدار `robots` ارسال می‌کند (پیش‌فرض `index, follow`)،
- و `SiteDataService::pageMeta()` همیشه `robots` برمی‌گرداند،

عملاً برای همه‌ی پست‌ها، صفحات و دوره‌ها منطق پارامترهای کوئری هیچ‌وقت اجرا نمی‌شود.

پیشنهاد: ترکیب سخت‌گیرانه — اگر هر کدام از دو منبع `noindex` داشت، نتیجه `noindex` باشد.

### ۲-۴. `HomeController` متای قابل‌ویرایش در ادمین را بازنویسی می‌کند

فایل: `app/Http/Controllers/Site/HomeController.php`

```php
$seo = $this->seo->forPage('home', [
    'title' => 'راهبر حساب | موسسه آموزش حسابداری و خدمات مالی',
    'description' => '...',
    'keywords' => '...',
]);
```

عنوان و توضیحات هاردکد شده و به‌عنوان override به `forPage` داده می‌شود؛ پس `meta_title`/`meta_description` صفحه‌ی
`home` که از ادمین ویرایش می‌شود هیچ اثری ندارد. این مقادیر باید **پیش‌فرض** باشند، نه override
(جای درستشان `SiteDataService::defaultPageMeta()` است).

### ۲-۵. متا دو بار پردازش می‌شود و قرارداد ورودی `seo` مبهم است

`SiteController::renderSystemPage()` همیشه `forPage($slug, $data['seo'])` را صدا می‌زند. اما:
- `HomeController` و `PageController::show` قبلاً خروجی **کامل** `meta()`/`forPage()` را در `seo` گذاشته‌اند.
- بقیه‌ی جاها override خام می‌فرستند.

یعنی `seo` گاهی «متای نهایی» است و گاهی «override»، و متای صفحه دو بار ساخته می‌شود (دو کوئری `CmsPage` و
دو بار محاسبه‌ی canonical). همین ابهام باعث مشکل بند ۲-۴ شده.

پیشنهاد: یک قرارداد واحد — کنترلرها فقط override خام بفرستند و فقط یک لایه `meta()` را صدا بزند. ساختن یک DTO
(مثلاً `SeoMeta`) به‌جای آرایه‌ی آزاد هم این خطاها را در زمان توسعه آشکار می‌کند.

---

## ۳. متوسط

### ۳-۱. منطق تکراری بین `UrlGenerator` و `PermalinkService`

- regex تشخیص پسوند فایل (`/\.[a-zA-Z0-9]{1,8}$/`) در هر دو کلاس و در `.htaccess` تکرار شده.
- لیست مسیرهای بدون اسلش در `UrlGenerator` هاردکد است (`admin`, `panel`, `api`, ..., `migrated-wp`) و با
  `config('seo.reserved_prefixes')` هماهنگ نیست (`migrated-wp` اصلاً در کانفیگ نیست).

پیشنهاد: `PermalinkService::normalizePath()` تنها منبع باشد و `UrlGenerator` از آن استفاده کند؛ لیست مسیرهای بدون اسلش
به `config/seo.php` منتقل شود.

### ۳-۲. بازنویسی کامل سرویس `url` برای یک کار ساده

`AppServiceProvider` بایندینگ `url` را کاملاً با کلاس سفارشی جایگزین کرده و متد `format()` را override می‌کند. این به
جزئیات داخلی Laravel وابسته است و با هر ارتقای نسخه ممکن است بشکند. Laravel برای همین کار
`URL::formatPathUsing()` را ارائه می‌دهد که بدون جایگزینی کل سرویس، روی مسیر خروجی اعمال می‌شود.

### ۳-۳. ترتیب مسیرهای catch-all شکننده است

در `routes/web.php` مسیرهای `/{category}/{slug}` و `/{slug}` **قبل از** `/cart`، `/checkout`، `/login`، `/panel`
و `/admin` تعریف شده‌اند. درست کار کردن سایت فقط به این وابسته است که هر مسیر جدید دستی به
`seo.reserved_prefixes` اضافه شود. اگر کسی مسیری اضافه کند و این را فراموش کند، مسیرش بی‌صدا به `PageController`
می‌رود و ۴۰۴ می‌دهد.

پیشنهاد: مسیرهای catch-all آخر فایل قرار بگیرند (یا با `Route::fallback`)، و لیست reserved فقط برای اعتبارسنجی slug
در ادمین استفاده شود.

### ۳-۴. تولید sitemap بدون کش و با بارگذاری کامل رکوردها

`SeoService::postSitemapUrls()` و بقیه با `->get()` همه‌ی رکوردها را در حافظه می‌آورند و با هر درخواست دوباره اجرا
می‌شوند. همچنین:
- `with('categories')` در کوئری پست‌ها eager load می‌شود، ولی `PermalinkService::post()` از `$post->category` استفاده
  می‌کند؛ باید بررسی شود که N+1 ایجاد نکند.
- همه‌ی تگ‌ها و دسته‌ها، حتی بدون هیچ پست منتشرشده، در sitemap می‌آیند (صفحه‌ی خالی ایندکس‌پذیر).

پیشنهاد: `Cache::remember` با پاک‌شدن در Observer مدل‌ها، `cursor()`/`lazy()`، و فیلتر `whereHas('posts')`.

### ۳-۵. `SitemapController::renderSitemap()` خروجی سرویس را دوباره نرمال می‌کند

```php
$path = parse_url($item['loc'], PHP_URL_PATH) ?: '/';
$item['loc'] = $permalinks->absolute($path);
```

سرویس URL مطلق تولید کرده، ولی کنترلر دوباره آن را می‌شکند و می‌سازد (و query string را بی‌صدا حذف می‌کند).
این نشانه‌ی ناسازگاری بین متدهای سرویس است: `lawSitemapUrls()` از `route()` استفاده می‌کند و بقیه از
`PermalinkService`، و برای یکدست شدن این وصله در کنترلر اضافه شده. مشکل باید در سرویس حل شود. استفاده از
`app(...)` داخل کنترلری که خودش DI دارد هم ناسازگار است.

### ۳-۶. کد مرده و باقی‌مانده‌ی قالب قبلی

| مورد | محل |
|---|---|
| `forProduct()` و `productSchema()` (اسکیمای `SoftwareApplication` با قیمت ۰ و «دمو رایگان») — بدون استفاده | `SeoService` |
| `productSitemapUrls()` آرایه‌ی خالی برمی‌گرداند ولی مسیر `/product-sitemap.xml` هنوز فعال است | `SeoService`، `routes/web.php` |
| `ContactController::index()` — هیچ مسیری به آن اشاره نمی‌کند و کار `PageController::contact()` را تکرار می‌کند | `ContactController` |
| slugهای `services` و `why-bisan` (از قالب «بیسان») در sitemap و لیست reserved | `SeoService::pageSitemapUrls()`، `config/seo.php` |
| بلاک `if` خالی با کامنت «already exists» | مایگریشن `2026_10_19_100000_add_seo_parity_columns` |
| prefix `p` با کامنت «ریدایرکت به /{slug}/» در حالی که چنین ریدایرکتی وجود ندارد | `config/seo.php` |

### ۳-۷. `/sitemap.xml` به‌جای ایندکس، sitemap پست‌ها را برمی‌گرداند

`SitemapController::legacy()` همان `posts()` را برمی‌گرداند. هر کسی (یا هر ابزاری) که `/sitemap.xml` را بخواند فقط پست‌ها
را می‌بیند و صفحات، دوره‌ها و قوانین را نمی‌بیند. باید به `index()` اشاره کند یا به `/sitemap_index.xml` ریدایرکت شود.

### ۳-۸. مقدار `robots` بدون محدودیت اعتبارسنجی می‌شود و گزینه‌هایش سه جا تکرار شده

- `PostRequest`، `PageRequest` و `Admin\CourseController` فقط `string|max:50` چک می‌کنند؛ هر رشته‌ای قابل ذخیره است.
- در `PageRequest` اجباری است و در بقیه اختیاری (ناسازگار).
- لیست گزینه‌ها (`index, follow`, ...) در سه ویوی ادمین کپی شده.
- `Admin\PostController::validatePost()` قوانین اعتبارسنجی خودش را دارد و `PostRequest` را تکرار می‌کند.

پیشنهاد: یک Enum (مثلاً `App\Enums\RobotsDirective`) + قانون `Rule::enum(...)` + یک Blade component مشترک.

### ۳-۹. مایگریشن غیرقطعی (`hasColumn` در همه‌جا)

مایگریشن `add_seo_parity_columns` همه‌ی ستون‌ها را با `if (! Schema::hasColumn(...))` اضافه می‌کند. این یعنی ساختار
دیتابیس به ترتیب اجرای مایگریشن‌های قبلی وابسته است و `down()` ممکن است ستونی را حذف کند که این مایگریشن نساخته.
همچنین ستون‌های `wp_post_id` ایندکس `unique` دارند ولی در `down()` ایندکس قبل از حذف ستون حذف نمی‌شود (در SQLite
مشکل ایجاد می‌کند).

---

## ۴. پایین

### ۴-۱. منطق و Service Locator داخل Blade
`layouts/site.blade.php` با `app(SeoService::class)->meta()` و `app(SidebarService::class)` سرویس را از داخل ویو
صدا می‌زند و لیست طولانی `routeIs([...])` را در خود دارد. این‌ها باید از View Composer یا کنترلر پاس داده شوند.

### ۴-۲. `og:type` همیشه `website` است
برای پست‌ها `article` و برای دوره‌ها مقدار مناسب باید از `meta()` قابل override باشد. همچنین تگ `hreflang` که فقط به
خود صفحه اشاره می‌کند (سایت تک‌زبانه) هیچ کاربردی ندارد.

### ۴-۳. سوءاستفاده از `meta()` برای ساخت URL مطلق
```php
'image' => $post->featured_image ? $this->seo->meta(['og_image' => $post->featured_image])['og_image'] : null,
```
در `BlogController::articleSchema()` کل متای صفحه فقط برای گرفتن یک URL مطلق ساخته می‌شود، چون `absoluteUrl()`
خصوصی است. باید عمومی شود یا به `PermalinkService` منتقل شود. خود `articleSchema` هم بهتر است مثل بقیه‌ی اسکیماها
در `SeoService` باشد.

### ۴-۴. وابستگی `SeoService` به `request()` سراسری
`defaultRobots()` و `canonicalUrl()` مستقیماً `request()` را صدا می‌زنند و نام routeها هم داخل سرویس هاردکد شده.
سرویس را خارج از چرخه‌ی HTTP (صف، کنسول، تست واحد) غیرقابل‌پیش‌بینی می‌کند. همچنین مدل‌ها با نام کامل
(`\App\Models\CmsPost`) داخل متدها آمده‌اند به‌جای `use`.

### ۴-۵. متن‌های هاردکد و ناسازگار
- توضیحات و کلمات کلیدی پیش‌فرض داخل `SeoService` و پسوند عنوان (`| بلاگ راهبر حساب`) در چند کنترلر تکرار شده؛
  جای آن‌ها `config/cms.php` یا فایل زبان است.
- برچسب breadcrumb یک‌جا «صفحه اصلی» و جای دیگر «خانه» است.
- `courseSchema()`: ضرب در `10` (تبدیل تومان به ریال) عدد جادویی است؛ `availability` همیشه `InStock` است؛ و
  `courseWorkload` در نبود داده مقدار ساختگی ۶۰ دقیقه می‌گیرد (داده‌ی نادرست در اسکیما بدتر از نبودنش است).

### ۴-۶. صفحه‌بندی خارج از محدوده ۲۰۰ برمی‌گرداند
`/blog/page/999/` و `/product/page/999/` لیست خالی با کد ۲۰۰ می‌دهند (soft 404). در حالی که برای دسته/تگ ناموجود
درست ۴۰۴ برگردانده می‌شود. کافی است اگر `$page > $posts->lastPage()` بود `abort(404)` شود.

---

## ترتیب پیشنهادی اصلاح

1. اصلاح `.htaccess` برای POST و مسیرهای ادمین/API (بند ۱-۱) — **فوری، قبل از هر استقرار روی Apache**.
2. تصمیم نهایی درباره‌ی محل نرمال‌سازی URL و پاک‌سازی کانفیگ/میدل‌ور بی‌اثر (بند ۱-۲).
3. رفع `increment('views')` (بند ۲-۱).
4. تعیین تکلیف ریدایرکت‌ها (بند ۲-۲).
5. یکدست کردن قرارداد `seo` در کنترلرها و رفع بازنویسی متای صفحه‌ی اصلی (بندهای ۲-۳ تا ۲-۵).
6. بقیه‌ی موارد به‌عنوان refactor تدریجی.
