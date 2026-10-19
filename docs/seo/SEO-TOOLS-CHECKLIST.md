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

## لانچ

- [ ] تکمیل [`LAUNCH-GATE.md`](LAUNCH-GATE.md) روی staging در برابر production وردپرس
