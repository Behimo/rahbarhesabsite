# نقشه URL وردپرس → لاراول (فاز اول — Parity)

دامنه canonical: `https://rahbarhesab.com` (بدون www)  
اسلش انتهایی: **الزامی** (مطابق وردپرس زنده)

منبع حقیقت: sitemap و لینک‌های production در اکتبر ۲۰۲۶.

## قرارداد

| نوع | الگوی وردپرس | رفتار لاراول فاز ۱ |
|-----|--------------|-------------------|
| خانه | `/` | 200 |
| ایندکس بلاگ | `/blog/` | 200 |
| صفحه‌بندی بلاگ | `/blog/page/2/` | 200 |
| نوشته | `/{category}/{slug}/` مثلاً `/blogs/.../`، `/news/.../` | 200 همان path |
| دسته نوشته | `/category/{slug}/` | 200 |
| صفحه‌بندی دسته | `/category/{slug}/page/2/` | 200 |
| برچسب | `/tag/{slug}/` | 200 |
| ایندکس دوره | `/product/` | 200 (معادل لیست دوره‌ها) |
| صفحه دوره | `/product/{slug}/` | 200 — **نه** `/courses/{slug}` |
| صفحه‌بندی دوره | `/product/page/2/` | 200 |
| دسته محصول | `/product-category/{slug}/` | 200 |
| برگه | `/{slug}/` مثلاً `/about-us/`، `/contact-us/` | 200 |
| `/?p={id}` | query قدیمی | 301 به permalink نهایی |
| فید | `/feed/` | 410 |
| wp-login / xmlrpc / wp-admin | — | 410 |
| uploads | `/wp-content/uploads/...` | 200 از `public/wp-content/uploads` |
| سایت‌مپ | `/sitemap_index.xml` | 200 |

## ممنوع در فاز ۱

- 301 کردن نوشته/دوره/برگه/دسته/برچسب زنده به `/blog/` یا `/courses/` یا `/p/`
- عوض کردن canonical به ساختار لاراولی جدید
- بازطراحی UI که جای H1، CTA و لینک‌های داخلی را جابه‌جا کند

## مسیرهای سیستمی لاراول (canonical جدا)

این‌ها روی وردپرس معادل عمومی ایندکس‌شده ندارند و noindex می‌مانند مگر خلافش ثابت شود:

`/cart/`, `/checkout/`, `/panel/`, `/admin/`, `/login/`, `/search/`

مسیرهای کمکی لاراول (`/laws/`, `/questions/`, `/courses/` به‌عنوان alias داخلی) فقط اگر به URL وردپرس اشاره نکنند؛ برای دوره‌ها لینک عمومی باید `/product/{slug}/` باشد.

## نرمال‌سازی مجاز (301)

- `http` → `https`
- `www.rahbarhesab.com` → `rahbarhesab.com`
- بدون اسلش انتهایی → با اسلش (برای pathهای محتوا)
- ریدایرکت‌های ازقبل‌ثبت‌شده Rank Math
- `/?p={id}` → permalink نهایی همان محتوا
