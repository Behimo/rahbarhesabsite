# چک‌لیست ابزار سئو — وضعیت پس از URL Parity

## ریدایرکت

- [x] CRUD ریدایرکت در `/admin/redirects`
- [x] ورود دسته‌ای / صادرات Rank Math (`seo:import-rankmath-redirects`)
- [x] تطبیق `/?p=` و query در middleware
- [x] مسیرهای مرده WP با 410 (`HandleGoneWordpressPaths`)
- [x] نرمال‌سازی trailing slash و www/https
- [x] هشدار زنجیره در importer
- [x] حذف ریدایرکت مهاجرتی `/product`→`/courses` و `/{slug}`→`/blog/{slug}`

## متا و ایندکس

- [x] فیلدهای SEO پست/برگه/دوره + robots/canonical
- [x] Import Rank Math در مهاجرت وردپرس
- [x] title/description/canonical/robots/OG/Twitter در layout
- [x] noindex جستجو/سبد/تسویه/پنل + staging
- [x] GTM / GSC از env

## سایت‌مپ و robots

- [x] `/sitemap_index.xml` و زیرنقشه‌ها با URL parity
- [x] حذف `public/robots.txt` ثابت
- [x] حذف noindex و unpublished از نقشه
- [x] دسته و برچسب در sitemap پست/دوره

## محتوا و URL

- [x] روت‌های `/product/`، `/{category}/{slug}/`، `/category/`، `/tag/`، pagination path
- [x] `ContentUrlRewriter` + `wp_shortcodes`
- [x] `public/wp-content/uploads` برای سرو فایل‌ها
- [x] alt تصویر شاخص از attachment در مهاجرت

## اسکیما

- [x] Course با IRR (تومان×۱۰) و instructor
- [x] Article با dateModified
- [x] BreadcrumbList

## صحت‌سنجی اصلاحات ۲۰۲۶-۱۰-۲۶

- [x] نرمال‌سازی و CMS redirect فقط GET/HEAD عمومی؛ bypass متدهای نوشتنی/OPTIONS، سیستم و فایل
- [x] سیاست production برای host/scheme/slash؛ query و path فارسی و trusted proxy
- [x] زنجیره/حلقه/410/query اختصاصی و مقصد www داخلی؛ حفظ `/product/{slug}/`
- [x] robots سخت‌گیرانه و متای قابل‌ویرایش خانه؛ یک نقطهٔ نهایی ساخت seoMeta
- [x] شمارندهٔ بازدید بدون تغییر lastmod/dateModified
- [x] sitemap دسته‌ای با TTL=300، namespace ریشه URL و invalidation مدل/دسته/تگ
- [x] حذف آرشیو خالی و حفظ والد دارای محتوای زیرشاخه
- [x] index کامل در `/sitemap.xml` و خروجی واقعی سازگاری `/product-sitemap.xml`
- [x] صفحه‌بندی خارج از محدوده 404؛ صفحهٔ اول خالی 200
- [x] Rule و component مشترک robots؛ حفظ directives گستردهٔ پشتیبانی‌شده
- [x] rollback/reapply مایگریشن‌ها فقط در SQLite حافظه‌ای
- [ ] اجرای migration جدید افزایش طول robots روی محیط استقرار پس از backup و بررسی staging؛ **اینجا اجرا نشده**
- [ ] تأیید روی Apache واقعی و موتور MySQL؛ تست داخلی PHP جای آن‌ها نیست

وضعیت هر ۲۲ بند، شواهد و حدود تضمین: [`SEO-CODE-REVIEW.md`](SEO-CODE-REVIEW.md).

## لانچ

- [ ] تکمیل [`LAUNCH-GATE.md`](LAUNCH-GATE.md) روی staging در برابر production وردپرس
