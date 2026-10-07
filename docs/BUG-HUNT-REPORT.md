# گزارش شکار باگ — Rahbar Hesab

| | |
|---|---|
| **تاریخ** | ۱۵ مهر ۱۴۰۴ — 2026-10-07 |
| **وضعیت مخزن** | commit `0effb69` (Refactor admin forms for improved layout and usability) |
| **پشته** | Laravel 12 · PHP 8.3.30 · PHPUnit 11.5.55 · Vite/Alpine |
| **روش** | شکار خواندنی ۴ بخش موازی (پول/پرداخت، احراز هویت/RBAC، ادمین/آپلود/XSS، فرانت‌اند JS) + اجرای کامل تست‌سوئیت + بازتولید مستقل یافته‌های کلیدی با تست‌های اثباتی موقت |
| **نتیجه سوئیت** | `Tests: 103, Assertions: 500, Failures: 1` (شکستگی قدیمی `PlatformTest::test_free_course_enrollment`) |
| **وضعیت** | 🔴 ۱ Critical · ۵ High تأییدشده · ۱۰ Medium تأییدشده · ۱۳ مشکوک (static) |

---

## روش و شواهد

- یافته‌های «تأییدشده» با تست PHPUnit بازتولید شدند (۱۶ تست اثباتی اجرا شد و نتیجه ثبت گردید).
- تست‌های اثباتی **موقت** بودند و بعد از اجرا حذف شدند؛ تست‌های دائمی باید در مرحله «تست رگرسیون» به‌صورت ثبت‌شده اضافه شوند (لیست تسک‌ها در انتها).
- یافته‌های «مشکوک» فقط استدلال ایستا هستند و هنوز بازتولید نشده‌اند؛ برچسب `suspected` دارند.
- تفکیک «باگ» از «hardening» رعایت شده: موارد بدون تریگر مشخص به‌عنوان hardening ذکر شده‌اند.

> ⚠️ **ادخل حین بررسی:** دو کامیت `8367aae` و `0effb69` (قابلیت Sidebar و بازآرایی فرم‌های ادمین) در طول جلسه در مخزن ظاهر شدند که کار این جلسه نبودند (احتمالاً سشن همزمان دیگر روی همین مخزن). این کامیت‌ها فایل‌های تست موقت شکار را نیز وارد تاریخچه کردند. در حال حاضر `git status` فقط یک مورد دارد:
> ` D tests/Feature/ZzHuntAdminTest.php` ← برای تمیزکاری: `git rm tests/Feature/ZzHuntAdminTest.php`

---

## ۱) یافته‌های تأییدشده (Confirmed)

### 🔴 Critical

**[Critical] ارتقای دسترسی عمودی: کارمند دارای `update_users` می‌تواند نقش `admin` را به خودش بدهد**
- **تریگر:** کاربری با نقش سفارشی فقط شامل `access_admin`+`view_users`+`update_users`، `PUT /admin/users/{خودش}` را با `role=admin` می‌فرستد. شرط نگهبان فعلی فقط تغییر نقش به *غیر* admin را رد می‌کند. `Gate::after`/`before` سپس همه مجوزها را برای admin بی‌قید و شرط می‌پذیرد → ارتقای کامل.
- **Where:** `app/Http/Controllers/Admin/UserController.php:61-63` (شرط خودویرایش)، `:93-97` (`syncRoles`)، `app/Providers/AppServiceProvider.php:50-56` (`Gate::before`)
- **Evidence:** confirmed — تست `test_staff_with_user_edit_permission_can_promote_themselves_to_admin` و نسخه «ارتقای دیگری» پاس شدند
- **Fix:** در `UserController::update`، تغییر نقشِ خودِ کاربرِ واردشده را کلاً ممنوع کنید (یا انتساب `admin` را مشروط به `Gate::allows(...)` کنید)؛ گروه مجوزی `admin.roles` را از `users` جدا کنید (`AccessCatalog.php:196-197`).

---

### 🟠 High

**[High] سبد خرید مهمان بعد از ورود نابود می‌شود**
- **تریگر:** کاربر مهمان کالا به سبد اضافه می‌کند (ردیف با `session_id` قدیمی) → لاگین → `session()->regenerate()` شناسه را عوض می‌کند و `mergeGuestCart()` بعد از آن با شناسه **جدید** جست‌وجو می‌کند → ادغام no-op، ردیف‌ها یتیم می‌مانند و سبد لاگین‌شده خالی است.
- **Where:** `app/Http/Controllers/Auth/AuthController.php:102-103` (OTP) و `:137-138` (رمز عبور)، `app/Services/CartService.php:105`
- **Evidence:** confirmed — تست بازتولید: بعد از login ردیف `cart_items` همچنان `user_id=null` ماند
- **Fix:** شناسه session را **قبل از** `regenerate()` ذخیره کنید و به `mergeGuestCart` بدهید، یا ادغام را قبل از regenerate انجام دهید.

**[High] خرید مجدد دورهٔ منقضی‌شده هیچ دسترسی‌ای نمی‌دهد (پول گرفته می‌شود، دسترسی نه)**
- **تریگر:** انقضای enrollment تمام می‌شود → `isEnrolledIn()` برمی‌گرداند false (چون `expires_at` گذشته) → کاربر می‌تواند دوباره بخرد → اما `enrollUser()` با `firstOrCreate` ردیف موجود را پیدا می‌کند و چون `status` هیچ‌جا از `active` به `expired` تغییر نمی‌کند (هیچ نویسنده‌ای برای `STATUS_EXPIRED` وجود ندارد)، وارد شاخه تمدید نمی‌شود → `expires_at` به‌روز نمی‌شود → بعد از پرداخت همچنان بدون دسترسی.
- **Where:** `app/Services/OrderService.php:217-234` (شرط `:228`)، `app/Models/User.php:103-114`، `app/Models/CourseEnrollment.php:12,53-64`
- **Evidence:** confirmed — تست بازتولید: بعد از فراخوانی `enrollUser` همچنان `expires_at` گذشته است
- **Fix:** در شرط `:228`، `expires_at` گذشته یا null را هم تمدید کنید (یا وضعیت را هنگام انقضا واقعاً `expired` کنید).

**[High] کاربران مسدود/تعلیق‌شده نشست‌های فعال قبلی را حفظ می‌کنند**
- **تریگر:** بن/تعلیق فقط جلوی **ورود جدید** را می‌گیرد؛ نشست زنده تا انقضای طبیعی (۱۲۰ دقیقه) به `/panel`، checkout، دانلود درس و پنل ادمین دسترسی دارد.
- **Where:** `app/Models/User.php:116-119` فقط در `AuthController.php:45,90,124` و `Admin/AuthController.php:37` صدا زده می‌شود؛ `bootstrap/app.php:22-34` هیچ middleware وضعیت ندارد
- **Evidence:** confirmed — تست: بعد از `status=banned`، `/panel` و `/panel/profile` همچنان 200
- **Fix:** چک `isBlocked()` در پایپلاین auth (مثلاً داخل `EnsureUser`/`EnsureCmsAdmin`) + `Auth::logout()` و 403.

**[High] XSS ذخیره‌ای از طریق نام فایل رسانه در مدیا‌پیکر ادمین (ارتقا از instructor به ادمین)**
- **تریگر:** دارندهٔ فقط مجوز `media` (مثلاً instructor) فایلی با نام `<img src=x onerror=…>.jpg` آپلود می‌کند؛ نام خام ذخیره و در `innerHTML` فرم پست درج می‌شود → لحظه‌ای که ادمین/ویرایشگر فرم پست را باز کند، payload در session او اجرا می‌شود.
- **Where:** `resources/views/admin/posts/form.blade.php:238` (interpolation خام در `innerHTML`)، `app/Http/Controllers/Admin/MediaController.php:46` (`getClientOriginalName()` بدون پاک‌سازی)، `AccessCatalog.php:174` (instructor ← media)
- **Evidence:** confirmed — کد بازخوانی و اثبات node تأیید شد
- **Fix:** ساخت المان با `createElement`/`textContent` (یا escape کامل) + ذخیرهٔ نام hash‌شده سرور و نمایش نام اصلی فقط به‌صورت escape.

**[High] محدودیت‌های کوپن فقط هنگام ساخت سفارش چک می‌شود + سفارش‌های pending تکراری از یک سبد**
- **تریگر:** کاربر `POST /checkout` را چند بار (دابل-کلیک/شبیه‌سازی) می‌فرستد → سبد فقط در `markPaid` پاک می‌شود، پس چند سفارش pending با **یک** تخفیف ساخته می‌شود؛ `recordUsage` بدون اعتبارسنجی مجدد اجرا می‌شود → دور زدن `usage_limit_total` / `usage_limit_per_user` / انقضای کوپن. همچنین کوپنی که ادمین بین ساخت سفارش و پرداخت غیرفعال کرده باشد، همچنان اعمال می‌شود. `used_count >= limit` هم check-then-act بدون قفل است.
- **Where:** `app/Http/Controllers/Site/ShopController.php:141-149` (بدون نگهبان سفارش pending تکراری)، `app/Services/OrderService.php:125-127`، `app/Services/CouponService.php:127-143`، `app/Models/Coupon.php:51-77`
- **Evidence:** suspected (static) — تریگر مشخص: دو POST پشت‌سرهم قبل از پرداخت
- **Fix:** اعتبارسنجی مجدد کوپن داخل `markPaid()` قبل از `recordUsage` + مسدود کردن ساخت سفارش pending از همان سبد (یا ثبت مصرف در زمان ساخت با ایندکس یکتا).

---

### 🟡 Medium

**[Medium] منو با `javascript:` URL → XSS ذخیره‌ای در همه صفحات عمومی**
- **Where:** `app/Http/Requests/Admin/MenuTreeRequest.php:75-77` (فقط non-empty)، `resources/views/partials/nav-link.blade.php:66,68`
- **Evidence:** confirmed — تست `test_menu_item_accepts_javascript_scheme_url` پاس شد
- **Fix:** قاعده `url:ascii` با پذیرش فقط http/https/نسبی + `rel="noopener noreferrer"` هنگام `target="_blank"`.

**[Medium] نقش editor می‌تواند HTML خام شامل اسکریپت ذخیره کند که برای بازدیدکنندگان خام رندر می‌شود**
- **Where:** sinks: `resources/views/pages/blog/show.blade.php:55`، `pages/courses/learn.blade.php:36`، `blocks/html.blade.php:2`، `blocks/text.blade.php:3`، پیش‌نمایش ادمین `routes/web.php:212`
- **Evidence:** confirmed — تست نقش editor با payload `<img src=x onerror=…>` پاس شد
- **Fix:** پاک‌سازی HTML هنگام ذخیره برای نقش غیر admin یا محدود کردن بلوک HTML خام به `ROLE_ADMIN`.

**[Medium] ایمپورت: بدون تراکنش، بدون اعتبارسنجی ساختاری، بدون flush کش → اعمال ناقص و کش عمومی قدیمی**
- **Where:** `app/Services/ImportExportService.php:24-36`، `app/Http/Controllers/Admin/ImportExportController.php:26-32`، `app/Http/Requests/Admin/CmsImportRequest.php:14`؛ flushهای صدازده‌نشده: `SiteDataService.php:670`، `HomeContentService.php:48`، `CacheService.php:39`
- **Evidence:** confirmed — دو تست پاس شد (کش تا ۱ ساعت مقدار قدیمی؛ اعمال تنظیمات قبل از خطا و بدون rollback)
- **Fix:** `DB::transaction` + استفاده از `CmsSetting::set` + `CacheService::flush…` + اعتبارسنجی فهرست بخش‌ها و `slug` اجباری + `max` حجم.

**[Medium] ذخیره بیلدر با ساختار نامعتبر بلاک → صفحه بیلدر ادمین 500 می‌شود**
- **Where:** `app/Http/Requests/Admin/BuilderSaveRequest.php:26-29` (فقط `array`)، `app/Services/BuilderCanvasRenderer.php:25` (`array_map(function (array $block)…` ← TypeError)، ذخیره در `PageBuilderController.php:42-56`
- **Evidence:** confirmed — تست `…_admin_builder_editor_500s…` پاس شد (صفحات عمومی به‌لطف try/catch در `BlockRenderer` سالم‌اند)
- **Fix:** اعتبارسنجی `blocks.*.type` در FormRequest + مقاوم‌سازی `normalize()`.

**[Medium] آپلود محصول پسوند کلاینت را نگه می‌دارد — دور زدن blocklist با پسوندی مثل `.pht`**
- **تریگر:** فایل PHP با نام `shell.pht` (نه در blocklist: php/phtml/phar) عبور می‌کند و در مسیر وب‌دسترس storage ذخیره می‌شود.
- **Where:** `app/Http/Controllers/Admin/ProductController.php:96-98`
- **Evidence:** confirmed — تست پاس شد
- **Fix:** ذخیره با `$file->store()` (نام hash) + پسوند از `guessExtension()` در لیست سفید jpg/jpeg/png/webp + غیرفعال کردن engine در مسیر storage وب.

**[Medium] تغییر رمز پنل بدون رمز فعلی و بدون باطل‌سازی نشست**
- **تریگر:** نشست سرقتی (XSS/دستگاه مشترک) می‌تواند رمز جدید + ایمیل/تلفن را به مهاجم منتقل کند؛ قربانی لاگاوت نمی‌شود و مهاجم بعداً با OTP وارد می‌شود.
- **Where:** `app/Http/Requests/Panel/UpdatePasswordRequest.php:16-19`، `app/Http/Controllers/Panel/DashboardController.php:87-109`، `routes/web.php:156-158`
- **Evidence:** confirmed — تست بدون `current_password` پاس شد
- **Fix:** قاعده `current_password` (برای رمز و تغییر ایمیل/تلفن) + `$request->session()->invalidate()` بعد از موفقیت.

**[Medium] تست رگرسیت شکسته: `PlatformTest::test_free_course_enrollment`**
- **تریگر:** `enrollFree` بعد از ثبت‌نام رایگان به `courses.show` برمی‌گرداند، تست انتظار `courses.learn` دارد.
- **Where:** `app/Http/Controllers/Site/CourseController.php:308` vs `tests/Feature/PlatformTest.php:70`
- **Evidence:** confirmed — کل سوئیت: `Tests: 103, Failures: 1`
- **Fix:** تصمیم محصولی؛ اگر هدف رفتن مستقیم به `learn` است → `route('courses.learn', [$slug, null])` و در غیر این صورت به‌روزرسانی تست.

**[Medium] بخش تکراری بیلدر نامرئی رندر می‌شود (opacity:0) + id تکراری**
- **تریگر:** همان نوع بلاک دو بار در صفحه (بیلدر تکرار را می‌پذیرد) → `script.js` فقط اولین match را با `querySelector` bind می‌کند در حالی که CSS محتوا را پشت `.is-visible` نگه می‌دارد → بخش دوم کلاً خالی، دکمه‌های قبلی/بعدی مرده.
- **Where:** `public/site/script.js:177,208,266,543,569,819,845,871,896,922` vs `public/site/style.css:3282,4970`؛ بلاک‌ها: `resources/views/blocks/features.blade.php:12`، `partials/home/benefits.blade.php:1`، `partials/home/faqs.blade.php:1`
- **Evidence:** confirmed (node-harness) — فقط اولین بخش `is-visible` می‌گیرد
- **Fix:** `querySelectorAll` + حلقه per-section (الگوی درست کارousel در `script.js:296`) + حذف `id` تکراری.

**[Medium] ذخیره بیلدر بدون‌اجبار draft را publish می‌کند**
- **Where:** `app/Http/Controllers/Admin/PageBuilderController.php:51-56` (و `restore()` در `:89-92` بدون اعتبارسنجی بلاک)
- **Evidence:** confirmed — تست پاس شد
- **Fix:** publish فقط با درخواست صریح؛ حفظ `is_published`/`status` فعلی.

**[Medium] رمز/ایمیل/تلفن پنل بدون رمز فعلی (ادغام با بالا)** + **متن پاپاپ double-escaped**
- پاپاپ: `app/Services/PopupService.php:147-150` (`e()`/`nl2br(e())`) + `resources/views/components/site-popup.blade.php:14-17` (`{{ }}` دوباره escape) → بازدیدکننده `<br />` و `&amp;` تحت‌اللفظی می‌بیند.
- **Evidence:** confirmed — تست `test_popup_text_is_double_escaped_on_public_pages` پاس شد
- **Fix:** یک لایه escape کافی است؛ یکی را حذف کنید.

---

## ۲) یافته‌های مشکوک (Suspected — static، هنوز بازتولید نشده)

| شدت | یافته | Where | Fix پیشنهادی |
|---|---|---|---|
| Medium | callback پرداخت: GET بدون امضا و بدون auth → هر کسی با trackId می‌تواند پرداختِ pendingِ قربانی را failed و استخراج stock را آزاد کند (سناریو: پرداخت واقعی با stock آزاد → `markPaid` خطا → پول گرفته‌شده، سفارش failed) | `routes/web.php:121`، `ShopController.php:202-223`، `ZibalService.php:111-122` | نادیده‌گرفتن `success=0` برای پرداخت جوان یا callback امضاشده (HMAC) |
| Medium | `retryPayment` برای سفارش در حال پرداخت، جلسه پرداخت دوم می‌سازد → شارژ دوباره، یک تأمین | `ShopController.php:174-200`، `Order.php:66-69` | رد کردن retry وقتی Payment pending موجود است (یا بازگشت به همان URL) |
| Medium | payment به `success` خارج از تراکنش `markPaid` ثبت می‌شود → پول گرفته‌شده با سفارش unpaid و بدون reconciliation | `ZibalService.php:157-169`، `ZarinpalService.php:169-181`، `OrderService.php:99-130` | انتقال آپدیت payment داخل همان تراکنش |
| Medium | استرداد/لغو enrollment، license اسپات‌پلیر را revoke نمی‌کند (`spot_url` قابل اشتراک باقی می‌ماند)؛ reissue مکرر license زندهٔ اضافه می‌سازد | `OrderService.php:243-249`، `SpotPlayerService.php` (فقط POST)، `Admin/LicenseController.php:68-92` | فراخوانی endpoint revoke و علامت‌گذاری ردیف |
| Low | `markPaid` **کل** سبد کاربر را پاک می‌کند نه فقط اقلام سفارش (کالای افزوده بعد از checkout گم می‌شود) — بازخوانی و تأیید شد | `OrderService.php:132,90-94` | حذف فقط `shop_product_id`های `$order->items` |
| Low | `subtotal`/`total` از قفل‌نشدهٔ قبل از تراکنش محاسبه می‌شود ولی `OrderItem.price` از ردیف قفل‌شده ← واگرایی در ویرایش همزمان قیمت | `OrderService.php:33-35` vs `:38-53` | محاسبه داخل تراکنش از `$lockedProducts` |
| Low | شماره سفارش `substr(uniqid(),-6)` با ایندکس یکتا → برخورد همزمانی = 500 | `Order.php:71-74` | `Str::ulid()` یا `random_int` + retry |
| Low | callback همزمان: بعد از ۲۵۰ms خواب، به کاربرِ پرداخت‌کرده صفحه «ناموفق» نشان داده می‌شود | `ZibalService.php:126-131`، `ZarinpalService.php:131-136` | poll قفل تا ~۳ ثانیه یا صفحه «در حال تأیید» |
| Low | کوپن: بدون throttle + پیام خطای متمایز («معتبر نیست» vs «قابل استفاده نیست») = oracle حدس کدن | `routes/web.php:118`، `CouponService.php:55-85` | `throttle:coupon` + پیام یکسان |
| Low | محدودیت per-phone مسیر `otp-verify` کد مرده است (فقط IP اعمال می‌شود؛ کاهش ریسک با ۵ تلاش/کد) | `AppServiceProvider.php:89-91,105-118` | کلید limiter از `session('otp_phone')` |
| Low | کد OTP در پاسخ صفحه verify وقتی `APP_ENV=local` نمایش داده می‌شود (`.env` فعلی local+debug است) | `AuthController.php:72`، `auth/verify-otp.blade.php:41` | حذف کامل یا فقط در log/artisan |
| Low | `HandleCmsRedirects` می‌تواند `/login` را هم ریدایرکت کند (بدون اعتبارسنجی scheme در `to_path`) + `Schema::hasTable` در هر ریکوئست | `HandleCmsRedirects.php:16,20-35`، `RedirectRequest.php:17-26` | رد مقدار مطلق/`//` + افزودن `login`,`logout`,`up` به skip list |
| Low | `password login` با OR بدون گروه‌بندی ← تطبیق سایه‌ای روی ردیف با phone خالی | `AuthController.php:114-118` | گروه‌بندی `where(fn…)` |
| Low | بدون `trustProxies` → پشت LB همه یک بودجه throttle مشترک دارند؛ بدون `SESSION_SECURE_COOKIE`؛ توکن `CMS_API_TOKEN=change-me` بدون مسیر چرخش | `bootstrap/app.php`، `config/session.php:172`، `.env:27` | تعریف proxyها، پیش‌فرض secure در production، رد توکن پیش‌فرض |
| Low | `AccessCatalog::install()` در هر migrate دوباره مجوزهای پیش‌فرض نقش‌های سیستم را sync می‌کند (تغییر ادمین را برمی‌گرداند) | `AccessCatalog.php:421-445` | فقط برای نقش جدید؛ افزایشی (بدون revoke) |
| Low | UI: خطای کوپن بدون نمایش (`novalidate` بدون `$errors`)؛ دابل-کلیک «افزودن به سبد» ×۲؛ fetchهای ادمین بدون `catch`/`res.ok`؛ CKEditor CDN بدون fallback (بقیه init فرم را می‌کُشد)؛ مدیا‌پیکر فقط صفحه ۱؛ key نقشه Neshan در سورس؛ بستهٔ مردهٔ `magic-animations.js` با rAF/timer بدون cleanup؛ ناهماهنگی ورودی‌های Vite با `@vite` | `cart.blade.php:203`، `caches/show.blade.php:216`، `posts/form.blade.php:195,224`، `vite.config.js:16-61`، `resources/assets/js/app-logistics-fleet.js:24` | رفع موردی |

---

## ۳) نواحی پاک (بررسی شد، یافت نشد)

- **پول/ریاضی:** مبالغ integer تومان در کل زنجیره؛ تخفیف یک‌بار روی کل (نه per-line)؛ بدون قیمت از کلاینت؛ `total` clamped و ستون unsigned — تمیز.
- **تأیید مبلغ پرداخت:** verify سمت سرور + تطابق دقیق مبلغ (پرداخت ۱ ریال نمی‌تواند سفارش را paid کند) — تمیز (پوشش `ShopCheckoutTest`).
- **idempotency پرداخت موفق:** callback تکراری کوتاه‌شده، `markPaid` لاک‌دار، یکتایی enrollment/license — تمیز.
- **OTP:** باطل‌سازی کد بعد از موفقیت، `hash_equals`، ۵ تلاش، اتصال به session، throttle ارسال (۶/دقیقه + سرویس) — تمیز.
- **پوشش route ادمین:** همه ۱۴۸ مسیر داخل `cms.admin`+`cms.permission` به‌جز login/logout (CSRF) — تمیز.
- **مالکیت سبد:** `authorizeCartItem` جلوی دستکاری سبد دیگران را می‌گیرد — تمیز.
- **CSRF:** همه فرم‌ها و fetchها توکن دارند — تمیز.
- **مقادیر محرمانه:** `password`/`remember_token` در `$hidden`؛ توکن API فقط با `hash_equals` — تمیز.
- **پاپاپ:** اعتبارسنجی URL ناامن، اولویت، نقش‌ها — پوشش تست دارد — تمیز.

---

## ۴) لیست تسک‌ها (اولویت‌دار)

### P0 — قبل از هر استقرار عمومی
- [ ] **T1** بستن ارتقای نقش به `admin`: ممنوعیت تغییر نقش خود در `UserController::update` + جدا کردن گروه مجوزی `admin.roles` از `users` (+ تست رگرسیون دائمی برای «ارتقای خود» و «ارتقای دیگری») — `UserController.php:61-63`
- [ ] **T2** رفع گم‌شدن سبد مهمان هنگام ورود: ذخیره شناسهٔ session قبل از `regenerate()` و پاس دادن به merge (+ تست: مهمان → لاگین → سبد) — `AuthController.php:102,137`
- [ ] **T3** تمدید enrollment هنگام خرید مجدد دورهٔ منقضی (شرط `OrderService.php:228`) (+ تست: expires_at گذشته → خرید → isEnrolledIn=true)
- [ ] **T4** چک وضعیت کاربر در هر درخواست احرازشده: بن/تعلیق → 403 + logout (`EnsureUser`/`EnsureCmsAdmin`) (+ تست: بن بعد از لاگین)
- [ ] **T5** رفع XSS نام فایل رسانه در مدیا‌پیکر: escape در `posts/form.blade.php:238` + نام hash سرور در `MediaController.php:46`

### P1 — یکپارچگی پول و امنیت محتوا
- [ ] **T6** اعتبارسنجی مجدد کوپن در `markPaid` قبل از `recordUsage` + مسدود کردن سفارش pending تکراری از یک سبد (`ShopController.php:141`, `OrderService.php:125`)
- [ ] **T7** قاعده URL منو: فقط http/https/نسبی + `rel="noopener"` در `nav-link.blade.php:66-68` (+ تست دائمی)
- [ ] **T8** پاک‌سازی HTML خام برای نقش غیر admin (یا محدود کردن بلوک HTML به admin)
- [ ] **T9** تراکنش + اعتبارسنجی + flush کش در ایمپورت (`ImportExportService.php:24`, `CmsImportRequest.php`)
- [ ] **T10** اعتبارسنجی ساختار بلاک در `BuilderSaveRequest` + مقاوم‌سازی `BuilderCanvasRenderer` (+ تست: بیلدر 500 نشود)
- [ ] **T11** آپلود: نام hash + پسوند از محتوا/لیست سفید در `ProductController.php:96` (و بررسی خواهرخوانده‌های آپلود: Media/Plugin/Theme)
- [ ] **T12** قاعده `current_password` + `session()->invalidate()` در پنل (`UpdatePasswordRequest.php`)
- [ ] **T13** تعیین تکلیف `enrollFree` (redirect به learn یا اصلاح تست) و سبز کردن کل سوئیت (`CourseController.php:308`)

### P2 — استحکام و لبه‌ها
- [ ] **T14** حلقه `querySelectorAll` برای بخش‌های تکراری بیلدر در `public/site/script.js` + حذف id تکراری
- [ ] **T15** جلوگیری از publish خودکار در ذخیره بیلدر (`PageBuilderController.php:51`)
- [ ] **T16** رفع double-escape متن پاپاپ (یک لایه کافی است)
- [ ] **T17** امضای callback پرداخت یا نادیده‌گرفتن `success=0` برای پرداخت جوان (`ShopController.php:202`)
- [ ] **T18** جلوگیری از جلسه پرداخت دوم در `retryPayment` (`ShopController.php:174`)
- [ ] **T19** انتقال آپدیت `payment=success` داخل تراکنش `markPaid`
- [ ] **T20** پاک‌سازی فقط اقلام سفارش در `markPaid` (`OrderService.php:132`) + محاسبه subtotal از ردیف قفل‌شده
- [ ] **T21** throttle کوپن + پیام خطای یکسان (`routes/web.php:118`)
- [ ] **T22** ریدایرکت‌ها: رد scheme مطلق در `to_path` + افزودن `login/logout/up` به skip list (`HandleCmsRedirects.php`)
- [ ] **T23** تنظیمات امنیتی: `trustProxies`، `SESSION_SECURE_COOKIE` پیش‌فرض در production، رد `CMS_API_TOKEN=change-me`
- [ ] **T24** رفع شماره سفارش `uniqid` (برخورد احتمالی) و limiter مردهٔ `otp-verify` per-phone
- [ ] **T25** پاک‌سازی ریپو: `git rm tests/Feature/ZzHuntAdminTest.php` + مرور کامیت‌های `8367aae`/`0effb69` (که تست‌های موقت را وارد تاریخچه کردند)

### تست‌های رگرسیون دائمی (از تست‌های موقت بازتولیدشده)
- [ ] **T26** ثبت دائمی تست‌های تأییدشده در `tests/Feature/`: ارتقای نقش، سبد بعد از لاگین، تمدید enrollment، بنِ نشست، `javascript:` در منو، ایمپورت/کش، بلاک نامعتبر بیلدر، پسوند آپلود، `current_password`

---

*تولیدشده توسط شکار باگ opencode — ۱۵ مهر ۱۴۰۴ (2026-10-07) · HEAD: `0effb69`*
