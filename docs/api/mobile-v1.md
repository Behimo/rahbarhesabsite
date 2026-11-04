# API اپلیکیشن — نسخه ۱

پایه آدرس: `/api/v1/app`

هویت کاربر فقط با توکن شخصی Sanctum ثابت می‌شود. نشست مرورگر روی این مسیرها پذیرفته نمی‌شود و توکن اپ هم پنل وب را باز نمی‌کند. کلید لایسنس فقط از درخواست «تماشا» و فقط برای صاحب دوره برمی‌گردد.

## هدرها

| هدر | کاربرد |
|---|---|
| `X-App-Key` | کلید کلاینت (`APP_CLIENT_KEY`). روی همه درخواست‌ها لازم است. |
| `Authorization: Bearer {token}` | توکن کاربر، بعد از تأیید OTP. |
| `Accept: application/json` | اگر نباشد، سرور خودش JSON می‌خواهد. |
| `Idempotency-Key` | برای `POST /orders`. ۸ تا ۸۰ کاراکتر از حروف، عدد، `-` و `_`. |

توکن را در حافظه امن دستگاه نگه دارید. در لاگ، آدرس یا deep link نوشته نشود.

## پاسخ‌ها

لیست‌ها شکل استاندارد لاراول را دارند: `data`، `links`، `meta`.

خطا:

```json
{ "message": "متن فارسی", "code": "stable_code", "errors": {} }
```

`errors` فقط برای خطای اعتبارسنجی هست. `code` برای تصمیم اپ پایدار است.

| وضعیت | معنی |
|---|---|
| 200 | موفق |
| 201 | توکن یا سفارش جدید ساخته شد |
| 202 | لایسنس هنوز صادر نشده |
| 204 | خروج |
| 401 | کلاینت، توکن یا کد OTP پذیرفته نشد |
| 403 | حساب غیرفعال یا توکن بدون مجوز `app` |
| 404 | منبع پیدا نشد یا متعلق به کاربر دیگری است |
| 409 | دوره قبلاً خریداری شده، یا کلید تکرار با سفارش دیگری تداخل دارد |
| 422 | داده نامعتبر |
| 429 | محدودیت تعداد درخواست |

## ورود

`POST /auth/otp`

```json
{ "phone": "09121234567" }
```

پاسخ برای شماره تازه و شماره موجود یکسان است و کاربر را لو نمی‌دهد:

```json
{ "data": { "challenge_id": "…", "expires_in": 300 } }
```

`POST /auth/token`

```json
{
  "phone": "09121234567",
  "code": "123456",
  "challenge_id": "همان شناسه",
  "device_name": "Pixel 8"
}
```

پاسخ `201`:

```json
{
  "data": {
    "token": "1|rh_app_…",
    "token_type": "Bearer",
    "expires_at": "2026-12-03T10:00:00+03:30",
    "user": { "id": 1, "name": "کاربر", "mobile": "09121234567", "status": "active" }
  }
}
```

کد ورود وب در اپ قبول نمی‌شود و برعکس. `challenge_id` یک‌بارمصرف است. هر کاربر حداکثر `APP_MAX_TOKENS` دستگاه فعال دارد؛ دستگاه قدیمی‌تر خارج می‌شود. با مسدود شدن حساب یا تغییر شماره، همه توکن‌ها باطل می‌شوند.

`DELETE /auth/token` توکن جاری را باطل می‌کند و `204` برمی‌گرداند.

`GET /me` مشخصات همان توکن را برمی‌گرداند.

حساب `pending_activation` باید اول از وب‌سایت فعال شود. اپ آن را فعال نمی‌کند.

## دوره‌ها

بدون توکن کاربر هم باز است، ولی `X-App-Key` لازم است.

- `GET /courses?search=&category=&per_page=20`
- `GET /courses/{slug}`

قیمت‌ها عدد صحیح به تومان (`currency: IRT`) هستند. جزئیات شامل سرفصل و عنوان درس است. آدرس ویدیو، فایل دانلود و شناسه اسپات‌پلیر در پاسخ نیست. اگر توکن معتبر باشد، `is_owned` پر می‌شود.

## خرید

`POST /orders`

```json
{ "product_id": 10, "coupon_code": null, "gateway": null }
```

`gateway` اختیاری است. اگر فقط یک درگاه فعال باشد، همان استفاده می‌شود.

پاسخ `201` (یا `200` اگر همان `Idempotency-Key` تکرار شود):

```json
{
  "data": {
    "order": { "order_number": "ORD-…", "status": "pending", "total": 150000, "currency": "IRT" },
    "payment_url": "https://gateway.example/start/…"
  }
}
```

دوره رایگان همان‌جا `paid` می‌شود و `payment_url` برابر `null` است. خرید دوباره دوره‌ای که ثبت‌نام فعال دارد `409` با کد `course_already_owned` است.

اپ `payment_url` را در مرورگر باز می‌کند. بعد از بانک، سایت به اپ برمی‌گردد:

`{APP_DEEP_LINK}?order={order_number}&status=success|failed`

این آدرس لایسنس ندارد. اپ با توکن کاربر وضعیت را می‌خواند:

- `GET /orders`
- `GET /orders/{order_number}`

وضعیت صدور لایسنس در `items[].license_status` است (`pending`، `issued`، `failed`) و خود کلید نیست.

## دوره‌های من

- `GET /me/courses`
- `GET /me/courses/{course}` شناسه دوره است، نه شناسه محصول.
- `POST /me/courses/{course}/license` فقط وقتی کاربر دکمه تماشا را می‌زند.

پاسخ موفق `200` و هدر `Cache-Control: no-store`:

```json
{ "data": { "course_id": 4, "status": "issued", "license_key": "…", "spot_url": "…", "issued_at": "…" } }
```

اگر لایسنس هنوز ساخته نشده باشد پاسخ `202` با کد `license_pending` است. اپ کمی بعد دوباره همین درخواست را می‌زند.

### Checkout replay and bundle status

An `Idempotency-Key` binds the product, coupon code and requested gateway. Reusing it with different inputs returns `409 idempotency_conflict`; use a new key for a changed purchase. Retrying the original request returns the original order.

For bundles, `items[].license_status` considers every course: `revoked` takes priority over `failed`, then `pending`. It is `issued` only when all course licenses are issued. Missing licenses count as `pending`.
