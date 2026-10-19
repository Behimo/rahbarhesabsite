# برنامهٔ آزمون پیش از لانچ

> این سند چک‌لیست اجرای تست است، نه گواهی آماده‌بودن برای لانچ. نتیجهٔ واقعی هر اجرای CI/لوکال را جداگانه ثبت کنید. هیچ تستی روی پایگاه دادهٔ عملیاتی اجرا نشود.

## لایه‌ها و محیط ایزوله

- **Unit و Feature (پیش‌فرض):** `phpunit.xml` محیط `testing`، SQLite در حافظه، کش/سشن آرایه‌ای و mail آرایه‌ای دارد. گروه `mysql` از suite پیش‌فرض حذف شده است. قبل از اجرا بررسی کنید که cache کانفیگ این مقادیر را بازنویسی نکرده. فرمان: `php artisan test`، سپس `vendor/bin/pint --test`.
- **رقابت MySQL:** تست‌های `@group mysql` در `tests/Feature/Concurrency/` فقط روی DB موقت خالی. لوکال: `DB_CONNECTION=mysql DB_DATABASE=rahbarhesab_test php artisan test --group=mysql`. در CI: job `php-mysql-concurrency` در `.github/workflows/quality.yml`.
- **فرانت‌اند:** `npm ci` و `npm run build` روی محیط توسعه/CI. تست مرورگر و دسترس‌پذیری هنوز باید با اپ لوکال و دادهٔ مصنوعی و ابزار E2E تهیه و اجرا شوند؛ build جایگزین E2E نیست.
- **اتصال‌های بیرونی:** `Http::fake`، `Queue::fake` و `Storage::fake` برای تست خودکار؛ درخواست واقعی به زیبال/زرین‌پال، پیامک و اسپات‌پلیر در suite خودکار ممنوع است.
- **امنیت و وابستگی:** `composer audit` و `npm audit --omit=dev` در محیطی با دسترسی شبکه، همراه با تفسیر false-positiveها؛ این‌ها تست نفوذ نیستند.

## فازهای پوشش و معیار سبز

| فاز | هدف | فایل‌های کلیدی | معیار سبز |
|---|---|---|---|
| ۰ حیاتی | OTP، IDOR سفارش، لایسنس ادمین، کوپن ادمین، دیجیتال، refresh SpotPlayer | `OtpAuthFlowTest`, `OrderOwnershipTest`, `AdminLicenseOrderActionsTest`, `AdminCouponCrudTest`, `DigitalProductDeliveryTest`, گسترش `SpotplayerLicenseTest` | بدون skip حیاتی؛ پول/مالکیت/لایسنس assert شده |
| ۱ پنل/ادمین | پنل کاربر، gapهای CRUD CMS، import/export RBAC | `UserPanelTest`, `AdminCmsCrudTest`, گسترش `RbacRegressionTest` | مالکیت دادهٔ پنل + 403 نقش محدود |
| ۲ smoke روت | تقریباً همهٔ named GET routes | `RouteSmokeTest` | هیچ روت حیاتی بدون ردیف matrix؛ status در مجموعهٔ مورد انتظار |
| ۳ MySQL | سقف کوپن، stock، رقابت موازی | `Concurrency/PaymentCouponStockRaceTest` | job/گروه mysql سبز روی DB موقت |

## ماتریس پذیرش مبتنی بر ریسک

| حوزه | حالت موفق | حالت خطا / حمله / تکرار | تست‌های موجود/نقطهٔ شروع |
|---|---|---|---|
| ورود/حساب | OTP، رمز کاربران منتقل‌شده، فعال‌سازی و پنل | کد منقضی/تکراری، محدودیت تلاش، حساب غیرفعال، دسترسی به حساب دیگر | `OtpAuthFlowTest`, `PlatformTest`, `PendingActivationAuthTest`, `AccountSecurityRegressionTest`, `SecurityRegressionTest` |
| RBAC ادمین | مجوز درست برای مشاهده/ویرایش | 403 برای نقش فاقد مجوز، ارتقای نقش توسط خود، import/export غیرمجاز | `RbacTest`, `RbacRegressionTest`, `RoleManagementTest` |
| فروش | merge سبد، قیمت/کوپن/stock، خرید دوره و تحویل یک‌باره | بازگشت تکراری، تغییر مبلغ، چند درگاه، شکست/timeout، retry، سفارش متعلق به دیگری، محصول دیجیتال | `ShopCheckoutTest`, `PaymentFlowHardeningTest`, `CheckoutRegressionTest`, `MixedCartCouponTest`, `PurchaseAuditTest`, `OrderOwnershipTest`, `DigitalProductDeliveryTest`, `AdminCouponCrudTest` |
| محتوای آموزشی | ثبت‌نام، پیشرفت، فایل و لایسنس | کاربر غریبه، ثبت‌نام منقضی/لغوشده، URL امضاشدهٔ نامعتبر، job دیررس، refresh لایسنس | `LessonAccessTest`, `SpotplayerLicenseTest`, `PurchaseAuditTest`, `AdminLicenseOrderActionsTest` |
| CMS/فرم | انتشار، ویرایش، نسخه، رسانه، تماس، پرسش‌وپاسخ | پیش‌نویس عمومی، اعتبارسنجی، XSS، آپلود ممنوع، دسترسی خارج از نقش | `AdminContentRegressionTest`, `AdminCmsCrudTest`, `CmsExtensionsTest`, `ContactFormTest`, `CafeQuestionTest` |
| پنل کاربر | داشبورد، دوره‌ها، سفارش‌ها، اعلان‌ها | guest، دادهٔ کاربر دیگر | `UserPanelTest`, `ProfileTest` |
| API/SEO | دادهٔ منتشرشده، sitemap، canonical و مسیرهای قدیمی | توکن اشتباه، انتشار محتوای draft، open redirect، query، مسیر رزرو | `PublicApiContentTest`, `SitemapAndRedirectTest`, `SearchContentTest`, `LaunchContractsTest`, `RouteSmokeTest` |
| انتقال وردپرس | dry-run، import، audit و تکرارپذیری روی fixture | rollback، کاربر بدون موبایل، سفارش یتیم، حذف داده، صدور مجدد لایسنس | `WordpressMigrationTest`; هرگز به منبع واقعی در اجرای تست وصل نشود |
| رقابت/همزمانی | رزرو stock و سقف کوپن زیر قفل ردیفی | دو checkout موازی روی آخرین واحد، دو پرداخت با یک کوپن | `PaymentCouponStockRaceTest` (`@group mysql`) |
| عملیات | migration تازه، queue/schedule، build، سلامت `/up` | نبود worker/cron، راز نمونه، debug روشن، خطای asset | `routes/console.php`, `.github/workflows/quality.yml`؛ نیازمند بررسی استیجینگ |

## معیار توقف لانچ

1. هیچ تست حیاتیِ شکست‌خورده یا incomplete/skip بدون تعیین تکلیفِ مستند باقی نماند. عدد «پوشش» به‌تنهایی کفایت نمی‌کند؛ invariantهای پول، مجوز و دسترسی باید صریحاً assert شوند.
2. CI روی branch هدف سبز باشد (شامل job SQLite و job MySQL concurrency)؛ migration از صفر روی DB تازه اجرا شود؛ backup و بازیابی آزمایش شوند؛ تنظیمات production، TLS، worker، scheduler، لاگ و مانیتورینگ جداگانه تأیید شوند.
3. بدون staging مستقل و سندباکس مجاز، پرداخت واقعی، تحویل SMS، صدور/لغو SpotPlayer، عملکرد مرورگرها و رفتار زیر بار واقعی **تأییدنشده** هستند. سبز بودن تست mock‌شده گواه صحت این اتصال‌ها نیست. `RouteSmokeTest` جایگزین سناریوهای عمیق دامنه نیست.
4. انتقال **دادهٔ زندهٔ وردپرس آخرین فاز پروژه** است: تنها پس از بکاپ بازیابی‌شده، dry-run و تطبیق شمارش/مبالغ/دسترسی‌ها روی staging، تأیید مسئول لانچ و برنامهٔ rollback. این suite هیچ انتقال زنده‌ای انجام نمی‌دهد.

## وضعیت اجرای این نوبت

اجرای واقعی test/build/Pint/audit باید با خروجی فرمان ثبت شود؛ تا پیش از آن هیچ نتیجه‌ای «پاس‌شده» فرض نمی‌شود. تست‌های موجود را به‌خاطر قدیمی بودن سند `docs/PRODUCTION-READINESS.md` حذف یا سبزسازی صوری نکنید؛ هر شکست باید با رفتار مورد انتظار فعلی تطبیق داده شود.
