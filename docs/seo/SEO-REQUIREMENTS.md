# نیازمندی‌های سئو — راهبر حساب (فاز اول: URL Parity)

منبع خط‌قرمزها: فایل سئوکار + `docs/seo/URL-MAP.md`.  
دامنهٔ نهایی: `https://rahbarhesab.com` بدون `www`.  
اسلش انتهایی: **الزامی**.

## قانون طلایی فاز ۱

هر صفحهٔ ایندکس‌شدهٔ وردپرس با **همان path** و HTTP **200** سرو می‌شود.  
در cutover به `/blog/{slug}` یا `/courses/{slug}` یا `/p/{slug}` مهاجرت **نمی‌کنیم**.

301 فقط برای:

1. ریدایرکت‌های ازقبل‌ثبت‌شده Rank Math  
2. `/?p={id}` → permalink نهایی  
3. نرمال‌سازی دامنه (`http`→`https`، `www`→apex) و trailing slash  
4. مسیرهای مردهٔ وردپرس با **410** (`wp-login`, `xmlrpc`, `/feed/`، …)

لینک‌های داخلی باید مستقیم به path نهایی اشاره کنند (نه از روی 301).

## نقشه URL (خلاصه)

| محتوا | path نهایی |
|-------|------------|
| نوشته | `/{category}/{slug}/` |
| بلاگ | `/blog/` و `/blog/page/{n}/` |
| دسته نوشته | `/category/{slug}/` |
| برچسب | `/tag/{slug}/` |
| دوره | `/product/{slug}/` |
| لیست دوره | `/product/` |
| دسته محصول | `/product-category/{slug}/` |
| برگه | `/{slug}/` (مثل `/about-us/`) |
| uploads | `/wp-content/uploads/...` |

جزئیات: [`URL-MAP.md`](URL-MAP.md)

## متای Rank Math

برای هر نوشته/برگه/دوره از `postmeta` وارد و رندر شود:

- title، description، robots، canonical، OG/Twitter  
- بدون عنوان خالی یا عنوان تکراری سراسری  
- صفحات noindex قبلی نباید ایندکس شوند  

## سایت‌مپ و robots

- فقط روت لاراول (`/robots.txt`) — فایل ثابت `public/robots.txt` حذف شده  
- staging: `Disallow: /` یا هدر noindex  
- سایت‌مپ فقط URLهای 200 و indexable  

## محتوا و رسانه

- متن، H1–H3، جداول، ویدئو، alt تصاویر حفظ شوند  
- shortcode خام نمایش داده نشود (`wp_shortcodes` + `clean_html`)  
- لینک‌های داخل محتوا با `ContentUrlRewriter` به path نهایی  

## اسکیما و اندازه‌گیری

- Course (قیمت IRR = تومان×۱۰، مدرس، مدت) و Article + BreadcrumbList  
- GTM / GSC از `SEO_GTM_ID` و `SEO_GSC_VERIFICATION`  

## UI/UX

فاز ۱: parity چیدمان محتوا و محل CTA/منو/H1. بازطراحی عمدی ممنوع تا پایداری ترافیک.

## معیار قبول قبل از cutover

لیست کامل در [`LAUNCH-GATE.md`](LAUNCH-GATE.md).
