# گیت لانچ سئو — قبل از جایگزینی وردپرس

Staging باید password + noindex باشد. تا وقتی موارد زیر سبز نشده‌اند cutover نکنید.

## URL و وضعیت HTTP

- [ ] نمونه نوشته از sitemap وردپرس → 200 روی همان path
- [ ] نمونه دوره `/product/{slug}/` → 200
- [ ] `/category/{slug}/` و `/tag/{slug}/` → 200
- [ ] `/blog/page/2/` و `/product/page/2/` → 200 (نه ریدایرکت به صفحه ۱)
- [ ] برگه `/about-us/` و `/contact-us/` → 200
- [ ] URL جعلی → **404** واقعی (نه soft 404 با 200)
- [ ] `/feed/` و `/wp-login.php` → **410**
- [ ] `/?p={id}` → یک پرش **301** به permalink نهایی

## ریدایرکت Rank Math

- [ ] экспорт Rank Math با `php artisan seo:import-rankmath-redirects` وارد شده
- [ ] نمونه ریدایرکت‌ها یک پرش 301 بدون زنجیره/حلقه
- [ ] صفحه مهم بدون ریدایرکت حذف نشده

## متا و canonical

- [ ] view-source: title یکتا از Rank Math (نه عنوان پیش‌فرض سایت برای همه)
- [ ] meta description موجود
- [ ] canonical مطلق `https://rahbarhesab.com/.../` با trailing slash
- [ ] robots صفحات noindex قبلی حفظ شده
- [ ] جستجو/سبد/تسویه/پنل → noindex

## محتوا و رسانه

- [ ] H1 و بدنهٔ مقاله/دوره در HTML اولیه دیده می‌شود
- [ ] تصویر شاخص و alt موجود
- [ ] `/wp-content/uploads/...` نمونه → 200
- [ ] لینک داخلی نمونه به 404 یا زنجیره نمی‌رود
- [ ] shortcode خام در صفحه نیست

## Sitemap / robots

- [ ] `/robots.txt` بدون `Disallow: /` در production
- [ ] `/sitemap_index.xml` باز می‌شود و فقط URLهای indexable دارد

## اسکیما و آنالیتیکس

- [ ] Rich Results Test برای یک دوره و یک مقاله بدون خطا
- [ ] قیمت Course با IRR هم‌خوان (تومان×۱۰)
- [ ] GTM و/یا GSC verification در head

## UI parity و سرعت

- [ ] محل H1، سرفصل دوره، CTA ثبت‌نام نزدیک وردپرس است
- [ ] افت محسوس LCP خانه/دوره نسبت به WP دیده نمی‌شود

## دامنه

- [ ] `www` و `http` به `https://rahbarhesab.com` ریدایرکت می‌شوند
