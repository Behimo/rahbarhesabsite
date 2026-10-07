# ممیزی جریان پول — پروسه خرید

| | |
|---|---|
| **تاریخ** | 2026-10-07 |
| **کامیت پایه** | `a828e4b` |
| **دامنه** | سبد خرید → checkout → درگاه پرداخت → callback → verify → markPaid/fulfill |
| **روش** | شکار باگ‌های پنهان (read-only) + تست‌های خصمانه؛ بدون تغییر کد اپلیکیشن |
| **نتیجه** | **6 باگ تأییدشده (Confirmed)** — همه با تستِ قرمز اثبات شده‌اند |
| **وضعیت تست** | `Tests: 6 failed, 122 passed` — ۶ تست جدیدِ `PaymentFlowHardeningTest` قرمزند، ۱۲۲ تست قبلی بدون تغییر سبز |
| **فایل شواهد** | `tests/Feature/PaymentFlowHardeningTest.php` |

> این گزارش فقط «یافته» است؛ طبق روش شکار، هیچ کد اصلاحی اعمال نشده تا مالک پروژه
> ترتیب رفع را انتخاب کند. هر باگ پایین یک تستِ در حالِ شکست دارد که پس از رفع،
> باید سبز (regression) شود.

---

## خلاصه ریسک پولی

| # | شدت | یافته | وضعیت |
|---|---|---|---|
| 1 | Critical | پولِ ضبط‌شده + سفارش failed (استثنای `recordUsage` بعد از verify) | Confirmed |
| 2 | Critical | برداشت دوباره از کارت (verify پرداخت دوم روی سفارش paid) | Confirmed |
| 3 | High | ثبت موفقیت روی ردیف پرداختِ اشتباه (`latestOfMany`) | Confirmed |
| 4 | High | مبلغ شارژشده ≠ مبلغ نمایش‌داده‌شده (reuse سفارش pending بدون در نظر گرفتن کوپن/قیمت) | Confirmed |
| 5 | High | کال‌بکِ cancelِ پرداخت قدیمی، سفارشِ در حال پرداخت را failed می‌کند | Confirmed |
| 6 | Medium | پرداخت pending بدون reconciliation (callback گم‌شده = پول بدون تسویه) | Confirmed (غیابی) |

---

## یافته‌های تأییدشده (Confirmed)

### [Critical] پول ضبط شد ولی سفارش failed شد — استثنای کوپن بعد از verify موفق

- **توضیح:** اگر سقف مصرف کوپن (`usage_limit_total` / `usage_limit_per_user`) بین لحظه‌ی
  ساخت سفارش و لحظه‌ی callback پُر شود، درگاه پول را واقعاً کسر می‌کند (verify=100) ولی
  `CouponService::recordUsage` داخل تراکنش `markPaid` استثنا پرت می‌کند ← کل تراکنش
  rollback ← `markFailed`. نتیجه: مشتری پول داده، سفارش `failed`، دسترسی صادر نشد، و
  ردیف `payment` در وضعیت `pending` می‌ماند (پولِ کسرشده بدون رکوردِ تسویه‌شده).
- **Where:** `app/Services/OrderService.php:135-137` (فراخوانی `recordUsage` داخل تراکنش)،
  `app/Services/CouponService.php:136` (پرت کردن `RuntimeException`)،
  `app/Services/ZibalService.php:184-191` و `app/Services/ZarinpalService.php:195-202`
  (گیر انداختن استثنا ← `markFailed` بعد از verify موفق).
- **Evidence:** تست `test_coupon_limit_hit_after_order_creation_does_not_void_a_captured_payment`
  — FAILED با پیام «Gateway captured the payment (verify result 100) but the order was failed
  after coupon validation».
- **Fix:** بعد از verify موفقِ درگاه، هرگز وضعیت پولی را بازنگردان. `recordUsage` را
  مستقل از تراکنشِ paid (try/catch + لاگ + علامت‌گذاری سقف‌شکسته برای ادمین) اجرا کن؛
  ستون‌های `payment/status` باید مستقل از نتیجه‌ی کوپن success شوند. (این همان یافته‌ی
  «suspected» گزارش قبلی `BUG-HUNT-REPORT.md` است که اکنون **confirmed** شد.)

### [Critical] برداشت دوباره از کارت — verify پرداخت دوم روی سفارشِ قبلاً paid

- **توضیح:** با فعال بودن همزمان دو درگاه، یک سفارش می‌تواند دو `Payment` pending داشته
  باشد (هر بار checkout با درگاهِ متفاوت). شرط early-return در `verifyCallback` فقط وقتی
  خودِ همان payment موفق باشد برمی‌گرداند؛ پرداختِ دوم بعد از paid شدن سفارش همچنان
  وارد شاخه‌ی verify می‌شود، یعنی **درگاه پول را دوباره کسر می‌کند**، ولی چون
  `markPaid` زود return می‌کند، ستون‌های success هرگز روی این payment نوشته نمی‌شود و
  برای همیشه `pending` می‌ماند → charge دوباره، بدون رکوردِ تسویه، بدون استرداد خودکار.
- **Where:** `app/Services/ZibalService.php:125,142-170`،
  `app/Services/ZarinpalService.php:129,146-168` (نبودِ بررسی `order->isPaid()` قبل از
  `Http::post(.../verify)`، `app/Services/OrderService.php:105-107` (return زودهنگام)).
- **Evidence:** تست `test_second_gateway_payment_is_not_captured_after_order_is_paid`
  — FAILED روی `Http::assertNotSent(verify)` یعنی درخواستِ capture به درگاه فرستاده شد.
- **Fix:** قبل از صدا زدن verifyِ درگاه، روی **سفارش** (نه payment) lock بگیر؛ اگر
  `isPaid()` بود، این payment را با پیام «سفارش قبلاً پرداخت شده» failed کن و درگاه را
  صدا نزن. به‌علاوه هنگام settle شدن یک payment، بقیه‌ی paymentهای pending همان سفارش
  را خودکار failed/لغو کن.

### [High] موفقیت روی ردیف پرداختِ اشتباه نوشته می‌شود

- **توضیح:** `markPaid` فیلدهای success (status/ref_id/card/verified_at) را از طریق
  `$locked->payment()` به‌روزرسانی می‌کند که `hasOne(...)->latestOfMany()` است؛ یعنی
  **جدیدترین** ردیف، نه ردیفِ verifyشده. اگر payment دوم (جدیدتر) زودتر ایجاد شده باشد،
  موفقیتِ پرداختِ واقعی (مثلاً zibal) روی ردیفِ پرداختِ انجام‌نشده (zarinpal) نوشته
  می‌شود و ردیف واقعی تا ابد `pending` می‌ماند. صفحه‌ی موفقیت، صفحه‌ی شکست و پنل ادمین
  همگی از همین `payment()` می‌خوانند → نام درگاه، ref_id و شماره کارتِ نمایش‌داده‌شده
  غلط است → تطبیق بانکی/مالی می‌شکند.
- **Where:** `app/Services/OrderService.php:140` (`$locked->payment()?->update($paymentFields)`)،
  رابط `app/Models/Order.php:52-55`؛ خوانندگان: `resources/views/pages/shop/success.blade.php:75-78`،
  `failed.blade.php:64-67`، `admin/orders/show.blade.php:112-118`،
  `ShopController.php:238,264`، `Admin/OrderController.php:32`.
- **Evidence:** تست `test_success_callback_records_the_payment_that_was_actually_verified`
  — FAILED: ردیف zibal `pending` و ردیف zarinpal ناشناس `success` با ref_idِ zibal.
- **Fix:** `Payment`ِ verifyشده را به‌عنوان instance وارد `markPaid` کن و فیلدها را مستقیماً
  روی همان ردیف (داخل تراکنشِ سفارش) بروزرسانی کن؛ `latestOfMany` را فقط برای نمایشِ
  «آخرین تلاش» نگه دار، نه برای نوشتنِ نتیجه.

### [High] مبلغ شارژشده ≠ مبلغ نمایش‌داده‌شده (reuse سفارش pending)

- **توضیح:** `reusablePendingOrder` فقط `product_id:quantity` را مقایسه می‌کند؛ کوپن و
  قیمتِ snapshot نادیده گرفته می‌شوند. سفارشِ pendingِ قبلی بدون قفلِ قیمت، دوباره به
  درگاه می‌رود و درگاهِ قبلی (با همان مبلغ قدیمی) reuse می‌شود.
- **ماشه‌ی A (اضافه‌دریافت از مشتری):** سفارش ۱۰۰٬۰۰۰ ساخته می‌شود (کاربر به درگاه
  رفته و برنگشته)، کاربر برمی‌گردد کوپن ۱۰٪ می‌زند، صفحه‌ی checkout رقم **۹۰٬۰۰۰** را
  نشان می‌دهد، ولی `processCheckout` سفارش قدیمی را reuse می‌کند و **۱۰۰٬۰۰۰** کسر می‌شود.
- **ماشه‌ی B (کسر دریافت از فروشگاه):** سفارش با کوپن (۹۰٬۰۰۰) ساخته می‌شود، کاربر کوپن
  را حذف می‌کند، صفحه **۱۰۰٬۰۰۰** نشان می‌دهد، ولی **۹۰٬۰۰۰** شارژ می‌شود.
- **Where:** `app/Services/OrderService.php:318-341` (شرطِ تطابق فقط
  `shop_product_id:quantity`؛ بدون کوپن/قیمت/جمع).
- **Evidence:** تست‌های `test_reused_pending_order_picks_up_a_coupon_added_after_checkout`
  و `test_reused_pending_order_keeps_a_coupon_that_the_customer_removed` — هر دو FAILED.
- **Fix:** در شرط reuse، کدِ کوپن + snapshotِ قیمت/جمع سفارش را هم مقایسه کن؛ در صورت
  هر اختلاف، سفارش قدیمی را `markFailed` و سفارشِ تازه با مبلغِ به‌روزِ همان لحظه بساز
  (مبلغِ ارسالی به درگاه همیشه باید از `cartViewData` همان request تغذیه شود).

### [High] کال‌بکِ cancelِ پرداخت قدیمی، سفارشِ در حال پرداخت را failed می‌کند

- **توضیح:** مسیرِ شکستِ callback بدون هیچ بررسی‌ای کل سفارش را `markFailed` و stock را
  آزاد می‌کند — حتی اگر پرداختِ دیگری از همان سفارش همین حالا باز باشد (retry در جریان).
  بازپخش callback قدیمی (back/refresh روی URL قدیمی، token معتبر است) وضعیتِ پرداختِ
  فعال را خراب می‌کند. در محصولِ فیزیکی، آزاد شدن stock در این پنجره می‌تواند باعث شود
  reserve مجدد در `markPaid` شکست بخورد ← **پولِ کسرشده با سفارش failed** (همان سناریوی #1
  از مسیر دیگر).
- **Where:** `app/Services/ZibalService.php:129-140`، `app/Services/ZarinpalService.php:133-144`
  (فراخوانی بی‌قید `markFailed`)، `app/Services/OrderService.php:153-168` (`markFailed`
  بدون اطلاع از payment فعال).
- **Evidence:** تست `test_stale_cancel_callback_of_an_old_payment_cannot_fail_a_new_payment_attempt`
  — FAILED: سفارش بعد از کال‌بک stale از `pending` به `failed` رفت.
- **Fix:** فقط وقتی این payment، آخرین/تنها پرداختِ بازِ سفارش است سفارش را failed کن؛
  تا زمانی که paymentِ pending دیگری وجود دارد، نه سفارش را failed کن نه stock را آزاد کن
  (یا حداقل با قفلِ سفارش و بررسی `payments` باز تصمیم بگیر).

### [Medium] پرداخت pending بدون reconciliation — callback گم‌شده = پول بدون تسویه

- **توضیح:** تنها مسیرِ settle شدن پرداخت، ریدایرکتِ مرورگرِ کاربر به
  `/checkout/callback` است. اگر کاربر بعد از پرداختِ موفق به هر دلیل برنگردد (بستن تب،
  قطع شبکه، مسدود شدن ریدایرکت)، payment تا ابد `pending` می‌ماند: پول نزد درگاه است،
  سفارش pending، stock نگه داشته شده، و هیچ job زمان‌بندی‌شده‌ای وضعیت را نمی‌سنجد.
- **Where:** `routes/console.php` (هیچ Schedule‌ای برای verify پرداخت‌های pending نیست)؛
  مسیرهای `ZibalService::verifyCallback` / `ZarinpalService::verifyCallback` فقط از
  callback مرورگر صدا زده می‌شوند.
- **Evidence:** static — غیابِ مسیر کدی confirmed است؛ بازتولیدِ شکستِ شبکه لازم نیست.
- **Fix:** دستور زمان‌بندی‌شده (مثلاً هر ۱۵ دقیقه): paymentهای `pending` قدیمی‌تر از X
  دقیقه را با verifyِ idempotent (کدهای 101/201 از قبل پشتیبانی می‌شوند) تسویه یا fail کن
  و نتیجه را لاگ/اعلام کن.

---

## مناطق تمیز (بدون ایراد)

- **نگهبان مبلغ:** `PaymentAmount::matches` مبلغِ ردیف payment و (در صورت گزارشِ درگاه)
  مبلغِ ریالیِ verify را با جمعِ سفارش تطبیق می‌دهد؛ مسیر «مبلغِ نامعتبر» درست است و تست
  `test_callback_rejects_a_mismatched_gateway_amount` دارد. واحد (تومان ↔ ریال) یکجا و
  صحیح است (`Money::tomanToRials`).
- **توکن callback:** `hash_equals` + توکن ۴۰ کاراکتری در `gateway_payload` و session؛
  بدون توکن هیچ state‌ای تغییر نمی‌کند (تست `unknown_callback_gateway` سبز).
- **انتخاب درگاه از سمت کلاینت:** `resolveForCheckout` فقط نامِ فعال را می‌پذیرد (تست سبز).
- **مسیر رایگان (total ≤ 0):** به درگاه نمی‌رود، مستقیم markPaid؛ بدون پول در میان.
- **قفل verify:** `payment:verify:{id}` per-payment با `waitUntilSettled` برای هم‌زمانیِ
  دو کالِبک یکسان (cache=database در production — `.env` سالم است).
- **رزرو stock:** `decrement` شرطی + `lockForUpdate` با ترتیبِ id؛ آزادسازی در مسیر شکست؛
  تست‌های stock موجود سبزند.

---

## تشخیص‌های suspect (بازتولید نشده — فقط استدلال ایستا)

- **race دو POST همزمان checkout:** بررسی `reusablePendingOrder` (`OrderService.php:34`)
  خارج از تراکنش و بدون lock است؛ دو درخواست موازی می‌توانند دو سفارش pendingِ همزمان
  بسازند (دوبار رزرو stock + دو payment قابل پرداخت). خودترمیمی جزئی: checkout بعدی
  سفارشِ ناهماهنگ را fail می‌کند.
- **`retryPayment` بدون توجه به درگاهِ انتخابی:** `ShopController.php:182-183` هر
  paymentِ pendingِ باز را با هر `start_url`‌ای برمی‌گرداند (ممکن است کاربر درگاهِ دیگری
  انتخاب کرده باشد) — ظاهری/UX، پول در معرض نیست.

---

## روش اثبات

```bash
composer test                       # یا: php artisan test
php artisan test --filter=PaymentFlowHardeningTest
# انتظار پس از رفعِ هر یافته: آن تست سبز می‌شود
# وضعیت فعلی: Tests: 6 failed, 122 passed
```

ترتیب پیشنهادی رفع: **1 → 2 → 3 → 4 → 5 → 6** (اول جلوگیری از ضبط/حذف پولِ بدون
تحویل، بعد صحتِ رکوردها، بعد تطابق مبلغ، سپس وضعیت‌ها و reconciliation).
