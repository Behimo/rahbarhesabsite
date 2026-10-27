# پلن اجرایی: هم‌خوانی کامل آدرس‌ها با وردپرس (۳ تسک)

این پلن برای اجرا توسط یک ایجنت کدنویس نوشته شده. هر تسک مستقل است و باید به همین ترتیب اجرا شود:

1. **تسک ۱:** برگرداندن ریدایرکت ۳۰۱ برای آدرس‌های بدون اسلش آخر
2. **تسک ۲:** انتقال ۵۸۵ پرسش و پاسخ وردپرس و انتقال «کافه سوال» به آدرس `/questions/`
3. **تسک ۳:** ساخت ابزار مقایسه‌ی آدرس‌به‌آدرس وردپرس با Laravel و تولید نقشه‌ی کامل URL

قبل از شروع، این فایل‌ها را بخوان: `docs/seo/SEO-OVERVIEW.md`، `docs/seo/URL-MAP.md`، `config/seo.php`، `app/Services/PermalinkService.php`.

> **اصلاحیه‌ی بعد از اجرا (۱۴۰۵/۰۸/۰۵):** مقایسه با سایت زنده نشان داد دو قاعده‌ی این پلن اشتباه بود و اصلاح شد:
> 1. Rank Math قبل از `%sitename%` جداکننده اضافه **نمی‌کند**؛ قالب‌ها همان‌طور که نوشته شده‌اند جایگزین می‌شوند.
> 2. عنوان پیش‌فرض پرسش بدون متای اختصاصی `{عنوان} - راهبر حساب` است و پیشوند `پرسش و پاسخ :` ندارد. ۵۷۶ پرسش متای اختصاصی دارند و عنوانشان `پرسش و پاسخ : {عنوان} راهبر حساب` است.
>
> وضعیت فعلی در `SEO-OVERVIEW.md` بخش ۵ و در `URL-PARITY-REPORT.md` آمده است.

---

## قواعد کلی (برای همه‌ی تسک‌ها)

- پروژه Laravel است و روی Windows/Laragon اجرا می‌شود. شل PowerShell است.
- دیتابیس وردپرس از طریق connection به نام `wordpress` در دسترس است (`DB::connection('wordpress')`، پیشوند جدول‌ها `wp_` و خودکار اعمال می‌شود). در `WordpressMigrationService` با `$this->wp()` به آن دسترسی داری.
- **هیچ آدرس عمومی موجود را تغییر نده** مگر اینکه در این پلن صریحاً گفته شده باشد.
- **نام routeها را عوض نکن.** مثلاً routeهای `cafe-soal.*` در کل پروژه استفاده شده‌اند (ویوها، نوتیفیکیشن، تست‌ها). فقط آدرس (prefix) عوض می‌شود.
- **به `public/.htaccess` قانون سئویی اضافه نکن.** همه‌ی منطق آدرس باید در Laravel بماند.
- سبک کد را از فایل‌های اطراف بگیر. کامنت فقط برای محدودیت‌هایی بنویس که از خود کد پیدا نیست.
- مایگریشن‌هایی که قبلاً اجرا شده‌اند را ویرایش نکن. برای تغییر جدول، مایگریشن جدید بساز.
- بعد از هر تسک، کل تست‌ها را اجرا کن: `php artisan test`. همه باید پاس شوند.
- در پایان هر تسک، مستندات مربوط در `docs/seo/` را به‌روز کن (فایل‌ها در هر تسک مشخص شده‌اند).

---

## واقعیت‌هایی که از وردپرس زنده و دیتابیس آن استخراج شده

این‌ها بررسی شده‌اند. برای تصمیم‌گیری به آن‌ها تکیه کن:

**سایت‌مپ زنده (`https://rahbarhesab.com/sitemap_index.xml`):**

| سایت‌مپ | تعداد آدرس | نمونه |
|---|---|---|
| `post-sitemap.xml` | ۱۶۲ | `/blog/`، `/blogs/{slug}/` |
| `page-sitemap.xml` | ۳۲ | `/`، `/rahbardesk/`، `/pos-services/` |
| `product-sitemap.xml` | ۴۴ | `/product/`، `/product/{slug}/` |
| `qa-sitemap1.xml` + `qa-sitemap2.xml` | ۵۰۱ + ۸۵ | `/questions/`، `/questions/{slug}/` |
| `video-sitemap.xml` | ۶۹ | همان مقاله‌های بلاگ که ویدیو دارند (نوع محتوای جدا نیست) |
| `local-sitemap.xml` | ۱ | `/locations.kml` (Rank Math Local SEO) |

**پرسش و پاسخ‌ها در دیتابیس وردپرس (`post_type = 'qa'`):**
- ۵۸۵ منتشرشده (`publish`) و ۲ در زباله‌دان (`trash`؛ نباید منتقل شوند).
- آدرس: `/questions/{post_name}/`. آرشیو: `/questions/` و `/questions/page/{n}/`، با **۲۰ مورد در هر صفحه** (`posts_per_page = 20`، ۳۰ صفحه).
- نامک‌ها همه انگلیسی‌اند (هیچ‌کدام `%` ندارند)، مثل `faq-job-title-change-in-the-social-security-insurance-list`.
- `post_content`: متن سؤال (HTML، معمولاً با «سوال :» شروع می‌شود).
- `postmeta._qa_answer`: متن پاسخ رسمی (HTML، معمولاً با «جواب :» شروع می‌شود). همه‌ی ۵۸۵ مورد پاسخ دارند.
- `postmeta.reza_video_url` و `postmeta.reza_video_poster`: ویدیوی سؤال، در ۴۵۲ مورد. آدرس‌ها روی `dl.rahbarhesab.com` یا `www.dl.rahbarhesab.com` هستند.
- `postmeta.rank_math_title`: در ۵۷۶ مورد، قالبش `پرسش و پاسخ : %title% %sitename% `. `rank_math_description` فقط در ۱۸ مورد.
- `postmeta._thumbnail_id`: در ۱۲ مورد.
- `postmeta._wp_old_slug`: در ۴۸ مورد (نامک‌های قدیمی که وردپرس خودش به نامک جدید ۳۰۱ می‌کند).
- تاکسونومی: فقط `post_tag` (۵۸۸ اتصال). دسته‌بندی ندارد.
- نویسنده‌ها: سه کاربر وردپرس (`post_author` = 7411 با ۵۰۸ مورد، 4606 با ۳۶، 2848 با ۴۱).
- دیدگاه‌های تأییدشده (`comment_approved = '1'`): ۱۶۸ عدد (۱۰۲ از کاربر ثبت‌نامی، ۶۶ مهمان، ۸۹ مورد پاسخ به دیدگاه دیگر با `comment_parent > 0`).

**صفحه‌های زنده‌ی پرسش و پاسخ:**

| صفحه | title | H1 | canonical | robots |
|---|---|---|---|---|
| `/questions/` | `کافه سوال راهبر حساب - راهبر حساب` | `کافه سوال راهبر حساب` | خود آدرس | `follow, index` |
| `/questions/page/2/` | `کافه سوال راهبر حساب - صفحه 2 از 30 - راهبر حساب` | `کافه سوال راهبر حساب` | خود آدرس | `follow, index` |
| `/questions/{slug}/` | `پرسش و پاسخ : {عنوان} - راهبر حساب` | `{عنوان}` | خود آدرس | `follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large` |

صفحه‌ی هر سؤال یک تگ `<video>` دارد و اسکیمای آن فقط `BreadcrumbList` است.

**`_wp_old_slug` در انواع دیگر هم هست** و هیچ‌جا ریدایرکت نشده: `post` (۳۶)، `product` (۱۵)، `qa` (۴۸).

**وضعیت فعلی Laravel:**
- بخش پرسش و پاسخ روی `/cafe-soal/` است (کنترلر `App\Http\Controllers\Site\CafeQuestionController`، مدل‌های `CafeQuestion` و `CafeAnswer`).
- هیچ مسیری برای `/questions/` نیست و تعداد سؤال‌ها در دیتابیس صفر است.
- `/cafe-soal/` فقط روی استیج بوده و در گوگل ایندکس نیست.

---

## تسک ۱: ریدایرکت ۳۰۱ برای آدرس‌های بدون اسلش آخر

### هدف (طبق متن سئوکار، گزینه‌ی اول)
- آدرس با اسلش (`/blog/`) **مستقیم** ۲۰۰ بدهد، بدون هیچ ریدایرکتی.
- آدرس بدون اسلش (`/blog`) با **یک** ۳۰۱ به نسخه‌ی با اسلش (`/blog/`) برود.
- لینک‌های داخلی، canonical و سایت‌مپ با اسلش بمانند (همین الان درست‌اند؛ دست نزن).

### تغییرات

**۱. `app/Http/Middleware/NormalizeSeoUrl.php`**

خط فعلی:
```php
$target = $this->permalinks->rootUrl().$path;
```
و کامنت دو خطی بالای آن را حذف کن و به این برگردان:
```php
$target = $this->permalinks->absolute($path);
```
`absolute()` همزمان دامنه، https و اسلش آخر را نرمال می‌کند. پس `http://www.rahbarhesab.com/blog?x=1` با **یک پرش** به `https://rahbarhesab.com/blog/?x=1` می‌رود. بقیه‌ی منطق (فقط GET/HEAD، استثنای `isSystemPath` و `isFilePath`، حفظ query string) دست نخورد.

**۲. قابل تنظیم کردن محیط‌ها برای تست روی استیج**

سئوکار خواسته رفتار روی دامنه‌ی تست (`delicollection.ir`، با `APP_ENV=staging`) قابل بررسی باشد. در `config/seo.php` این خط:
```php
'normalization_environments' => ['production'],
```
را به این تغییر بده:
```php
'normalization_environments' => array_filter(array_map('trim', explode(',', (string) env('SEO_NORMALIZE_ENVS', 'production')))),
```
و در `.env.example` کنار بقیه‌ی متغیرهای `SEO_*` اضافه کن:
```
# روی استیج برای تست ریدایرکت اسلش: SEO_NORMALIZE_ENVS=production,staging
SEO_NORMALIZE_ENVS=production
```
نکته: در محیط غیر production، `PermalinkService::rootUrl()` از دامنه و پروتکل خود درخواست استفاده می‌کند. پس روی استیج فقط اسلش اضافه می‌شود و دامنه به `rahbarhesab.com` عوض نمی‌شود. این رفتار درست است؛ تغییرش نده.

**۳. تست‌ها: `tests/Feature/SeoUrlPolicyTest.php`**

توجه: کلاینت تست Laravel (`$this->get()`) اسلش آخر آدرس را حذف می‌کند. برای تست آدرس **با** اسلش از `Request::create(...)` و صدا زدن مستقیم میدل‌ور استفاده کن، مثل بقیه‌ی تست‌های همین فایل.

- `test_normalization_redirects_host_and_https_only_and_preserves_query` را به `test_normalization_combines_host_https_and_slash_and_preserves_query` برگردان با این انتظارها:
  - `GET http://www.rahbarhesab.com/blog?name=%D8%AA%D8%B3%D8%AA&utm_source=test` باید ۳۰۱ به `https://rahbarhesab.com/blog/?name=%D8%AA%D8%B3%D8%AA&utm_source=test` برود.
  - `HEAD http://www.rahbarhesab.com/blog` باید به `https://rahbarhesab.com/blog/` برود.
- `test_missing_trailing_slash_is_served_without_redirect_and_canonical_has_slash` را حذف کن و به‌جایش دو تست بنویس:
  - `test_missing_trailing_slash_redirects_once_to_slash_url`: در production، `GET https://rahbarhesab.com/blog` باید ۳۰۱ به `https://rahbarhesab.com/blog/` برود.
  - `test_slash_url_is_served_directly_without_redirect`: با `Request::create('https://rahbarhesab.com/blog/')` میدل‌ور باید درخواست را بدون تغییر عبور دهد (پاسخ `$next`).
- در `test_normalization_preserves_encoded_persian_path_and_respects_origin_flags` انتظار `Location` را برگردان به `'https://rahbarhesab.com/blog/'.$slug.'/?ref=test'`.
- یک تست جدید: `test_paginated_and_nested_paths_get_trailing_slash`. در production، `/blog/page/2` و `/category/x/page/3` باید ۳۰۱ به نسخه‌ی با اسلش بروند.
- یک تست جدید: `test_normalization_environments_are_configurable`. با `config(['seo.normalization_environments' => ['production', 'staging']])` و `env = staging`، `/blog` باید به `/blog/` روی **همان دامنه‌ی درخواست** ریدایرکت شود.
- تست‌های موجود که باید بدون تغییر پاس شوند: POST/PUT/DELETE، ادمین، API، فایل‌ها و `/courses/...` هرگز ریدایرکت نمی‌شوند؛ در محیط local هیچ ریدایرکتی نیست (`test_local_requests_do_not_force_production_origin`)؛ پروکسی معتبر حلقه نمی‌سازد.

**۴. مستندات**
- `docs/seo/SEO-REQUIREMENTS.md`: بند ۳ را این‌طور بنویس: «نرمال‌سازی دامنه (`http`→`https`، `www`→apex) و اسلش آخر آدرس با یک 301، فقط GET/HEAD عمومی، در محیط‌های `SEO_NORMALIZE_ENVS`. آدرس با اسلش مستقیم 200 می‌دهد.»
- `docs/seo/SEO-OVERVIEW.md`: بخش ۱ (قواعد اصلی)، بخش ۳ (بند «اسلش آخر آدرس») و جدول بخش ۱۲ (متغیر `SEO_NORMALIZE_ENVS`) را مطابق رفتار جدید اصلاح کن.
- `docs/seo/URL-MAP.md` همین الان درست است (بخش «نرمال‌سازی مجاز»)؛ دست نزن.

### معیار پذیرش تسک ۱
- [ ] `https://rahbarhesab.com/blog/` بدون ریدایرکت ۲۰۰ می‌دهد.
- [ ] `https://rahbarhesab.com/blog` با یک ۳۰۱ به `/blog/` می‌رود.
- [ ] `http://www.rahbarhesab.com/blog` با **یک** ۳۰۱ (نه دو تا) به `https://rahbarhesab.com/blog/` می‌رود.
- [ ] POST و مسیرهای سیستمی ریدایرکت نمی‌شوند.
- [ ] همه‌ی تست‌ها پاس می‌شوند.

---

## تسک ۲: پرسش و پاسخ روی `/questions/` و انتقال ۵۸۵ مورد از وردپرس

### هدف
- بخش «کافه سوال» Laravel روی **همان آدرس وردپرس** باز شود: `/questions/`، `/questions/page/{n}/` و `/questions/{slug}/`.
- همه‌ی ۵۸۵ پرسش و پاسخ منتشرشده، با همان نامک، همان پاسخ، ویدیو، دیدگاه‌ها و متای Rank Math منتقل شوند.
- عنوان، H1، canonical و robots مثل نسخه‌ی زنده باشند (جدول بالا).

### ۲-۱. مسیرها: `routes/web.php`

گروه فعلی:
```php
Route::controller(CafeQuestionController::class)->prefix('cafe-soal')->name('cafe-soal.')->group(function () {
```
را به `prefix('questions')` تغییر بده. **نام route همان `cafe-soal.` بماند.** یک route صفحه‌بندی هم داخل همین گروه، قبل از `/{slug}`، اضافه کن:
```php
Route::get('/page/{page}', 'index')->whereNumber('page')->name('index.paged');
```
همچنین `/{slug}` نباید `page` را بگیرد: `->where('slug', '^(?!page$)[^/]+$')`.

ریدایرکت آدرس قدیمی استیج (بالای گروه، کنار بقیه‌ی مسیرهای عمومی):
```php
Route::permanentRedirect('/cafe-soal', '/questions/');
Route::get('/cafe-soal/{path}', fn (string $path) => redirect('/questions/'.$path, 301))->where('path', '.*');
```
(اگر `HandleCmsRedirects` یا قاعده‌ی دیگری با این تداخل دارد، به‌جای route از `CmsRedirect` استفاده نکن؛ همین route کافی است.)

**توجه:** مسیرهای `/panel/questions` (پنل کاربر) و `/admin/questions` (ادمین) جدا هستند و تغییر نمی‌کنند.

### ۲-۲. `config/seo.php`
- در `reserved_prefixes` مقدار `'questions'` را اضافه کن (کنار `'cafe-soal'`؛ `'cafe-soal'` را هم نگه دار).
- `system_prefixes` تغییر نمی‌کند (`questions` محتوای عمومی است و باید اسلش آخر بگیرد).

### ۲-۳. مایگریشن جدید برای ستون‌های لازم

فایل جدید `database/migrations/2026_10_27_100000_add_wp_parity_columns_to_cafe_tables.php`:

جدول `cafe_questions`:
- `wp_post_id` از نوع `unsignedBigInteger`، nullable، unique، بعد از `id`
- `meta_title` از نوع `string(191)`، nullable
- `meta_description` از نوع `string(500)`، nullable
- `robots` از نوع `string(500)`، nullable
- `canonical_url` از نوع `string(500)`، nullable
- `og_image` از نوع `string(500)`، nullable
- `featured_image` از نوع `string(500)`، nullable
- `video_url` از نوع `string(500)`، nullable
- `video_poster` از نوع `string(500)`، nullable
- `body` را به `longText` تغییر بده (`->change()`)؛ متن HTML وردپرس ممکن است از `text` بزرگ‌تر شود.

جدول `cafe_answers`:
- `wp_comment_id` از نوع `unsignedBigInteger`، nullable، unique
- `parent_id` از نوع `foreignId`، nullable، `constrained('cafe_answers')->nullOnDelete()`
- `author_name` از نوع `string(191)`، nullable (برای دیدگاه مهمان‌ها)
- `user_id` را nullable کن (`->nullable()->change()`). کلید خارجی موجود (`cascadeOnDelete`) را نگه دار.
- `body` را به `longText` تغییر بده.

`down()` باید دقیقاً همین‌ها را برگرداند. قبل از حذف ستون‌های unique، ایندکس unique را drop کن.

### ۲-۴. مدل‌ها

**`app/Models/CafeQuestion.php`:**
- trait `HasTags` را اضافه کن (`App\Models\Concerns\HasTags`، کنار `HasCategories`).
- ستون‌های جدید را به `$fillable` اضافه کن (به‌جز `wp_post_id`؛ آن را مستقیم assign کن، مثل `CmsPost`). `created_at` و `updated_at` را fillable نکن؛ در مهاجرت مستقیم assign کن.
- متد `bodyHtml(): string`: اگر `wp_post_id` دارد، `clean_html($this->body)` برگرداند؛ وگرنه `nl2br(e($this->body))`. (سؤال‌های کاربران سایت جدید متن ساده‌اند و باید escape شوند.)
- `SitemapObserver` را برای `CafeQuestion` هم ثبت کن (در `AppServiceProvider` لیست مدل‌هایی که observe می‌شوند).

**`app/Models/CafeAnswer.php`:**
- `parent_id`، `author_name` را fillable کن.
- رابطه‌ی `replies(): HasMany` (روی `parent_id`) و `parent(): BelongsTo`.
- متد `authorDisplayName(): string`: `$this->user?->displayName() ?? $this->author_name ?? 'کاربر'`.
- متد `bodyHtml(): string`: اگر `wp_comment_id` دارد، `clean_html($this->body)`؛ وگرنه `nl2br(e($this->body))`.

`communityAnswers()` در `CafeQuestion` فقط پاسخ‌های سطح اول را برگرداند (`whereNull('parent_id')`) و در کنترلر `replies.user` را eager load کن.

### ۲-۵. دستور و سرویس مهاجرت

**دستور جدید:** `app/Console/Commands/MigrateWpQuestionsCommand.php`، با الگوی دقیق `MigrateWpPostsCommand` (چک اتصال، progress bar، خروجی آمار):
```
wp:migrate-questions {--dry-run : فقط شمارش بدون نوشتن} {--fallback-user= : شناسه‌ی کاربر Laravel برای سؤال‌هایی که نویسنده‌شان پیدا نشد}
```

**در `app/Services/WordpressMigrationService.php`** دو متد عمومی اضافه کن: `countQuestions(): int` و `migrateQuestions(bool $dryRun = false, ?int $fallbackUserId = null, ?callable $onProgress = null): array`. خروجی: `['created' => int, 'updated' => int, 'answers' => int, 'comments' => int, 'redirects' => int, 'conflicts' => list<string>]`.

منطق `migrateQuestions` برای هر پست `qa` با `post_status = 'publish'` (به ترتیب `ID`، با `lazyById(250, 'ID')` و `postMetaForIds()` دسته‌ای، مثل `migratePosts`):

1. **نامک:** `$slug = $this->normalizeWpSlug($post->post_name)`. **نامک نباید تغییر کند و پسوند نگیرد.** اگر رکوردی با همین نامک در `cafe_questions` هست که `wp_post_id` متفاوت یا خالی دارد (سؤالی که کاربر سایت جدید ساخته)، آن را **بازنویسی نکن**؛ پیام را به `conflicts` اضافه کن و برو سراغ بعدی.
2. **پیدا کردن یا ساختن:** با `CafeQuestion::withTrashed()->where('wp_post_id', $post->ID)` و اگر نبود، با نامک. اجرای دوباره نباید رکورد تکراری بسازد (idempotent).
3. **نویسنده:** `User::where('wp_id', $post->post_author)->value('id')`. اگر پیدا نشد، `--fallback-user`. اگر آن هم داده نشده، اولین کاربری که نقش ادمین دارد (همان منطقی که `cms:ensure-admin` استفاده می‌کند را پیدا کن و به کار ببر). اگر هیچ‌کدام نبود، خطای واضح بده و متوقف شو.
4. **فیلدها:**
   - `title` برابر `post_title`
   - `slug`
   - `body` برابر `app(ContentUrlRewriter::class)->rewrite($post->post_content)`
   - `status` برابر `CafeQuestion::STATUS_ANSWERED`
   - `answered_at` برابر `post_date`
   - `created_at` برابر `post_date` و `updated_at` برابر `post_modified` (مستقیم assign کن و برای این ذخیره `timestamps` را موقتاً خاموش کن یا از `saveQuietly` با `timestamps = false` استفاده کن، تا تاریخ‌ها همان تاریخ وردپرس بمانند).
   - `featured_image` برابر `$this->featuredImageUrl($meta)`
   - `video_url` برابر `normalizeMediaUrl` روی `reza_video_url`، به‌اضافه‌ی تبدیل `www.dl.rahbarhesab.com` به `dl.rahbarhesab.com`
   - `video_poster` برابر `normalizeMediaUrl` روی `reza_video_poster`
   - `wp_post_id` برابر `ID`
5. **متای Rank Math:** `$this->applyRankMathMeta($question, $meta)`. متای عنوان باید دقیقاً به این شکل در بیاید: `پرسش و پاسخ : {عنوان} - راهبر حساب`. اگر `resolveRankMathTemplate` فعلی این را تولید نمی‌کند (چون `%sitename%` بدون جداکننده می‌آید)، آن متد را طوری اصلاح کن که **قبل از `%sitename%` در انتهای قالب، اگر `%sep%` نیامده، ` - ` قرار بگیرد** (رفتار خود Rank Math). این تغییر روی مقاله‌ها و دوره‌ها هم اثر دارد؛ تست واحد برای هر دو حالت بنویس. اگر `rank_math_title` خالی است، `meta_title` را خالی بگذار؛ کنترلر پیش‌فرض را می‌سازد.
6. **پاسخ رسمی:** اگر `_qa_answer` خالی نیست: `CafeAnswer::updateOrCreate(['official_for_question_id' => $question->id], [...])` با `cafe_question_id`، `user_id` (همان نویسنده)، `body` برابر `rewrite(_qa_answer)`، `is_official = true`، `created_at = post_date`. ستون `official_for_question_id` یکتا است؛ از همین برای idempotent بودن استفاده کن.
7. **دیدگاه‌ها:** همه‌ی دیدگاه‌های تأییدشده‌ی این پست را به ترتیب `comment_ID` بخوان (`comment_approved = '1'`، `comment_type` در `['', 'comment']`). برای هر کدام `CafeAnswer::updateOrCreate(['wp_comment_id' => ...], [...])`:
   - `cafe_question_id`
   - `user_id` برابر `User::where('wp_id', $comment->user_id)->value('id')` یا null
   - `author_name` برابر `comment_author`
   - `body` برابر `comment_content`
   - `is_official = false`
   - `created_at = comment_date`
   - `parent_id`: شناسه‌ی `CafeAnswer` متناظر با `comment_parent` (چون به ترتیب ID خوانده می‌شوند، والد قبلاً ساخته شده). اگر والد پیدا نشد، null.
8. **تگ‌ها:** `$this->syncModelTags($question, (int) $post->ID, 'post_tag')`.
9. **شمارنده‌ها:** `answers_count` را برابر تعداد کل پاسخ‌های این سؤال (رسمی + دیدگاه‌ها) بگذار. `views_count` را صفر نکن اگر از قبل مقدار دارد.
10. **ریدایرکت‌ها:**
    - `$this->ensureRedirect('/?p='.$post->ID, '/questions/'.$slug.'/')`
    - برای هر مقدار `_wp_old_slug` این پست (ممکن است چند ردیف باشد؛ با `postmeta` مستقیم بخوان، نه `postMetaForIds` که فقط یک مقدار نگه می‌دارد): `ensureRedirect('/questions/'.$old, '/questions/'.$slug.'/')`، به شرط اینکه `$old !== $slug`.
11. **dry-run:** هیچ چیزی ننویسد؛ فقط `created` و `updated` و `conflicts` را بشمارد.

**ریدایرکت نامک‌های قدیمی برای مقاله‌ها و دوره‌ها:** همین منطق `_wp_old_slug` را در `migratePosts` (مقصد: `PermalinkService::post`) و `migrateCourses` (مقصد: `/product/{slug}/`) هم اضافه کن. مبدأ برای مقاله: `/{category-slug}/{old}` (همان دسته‌ی فعلی) و برای دوره: `/product/{old}`.

**محدودیت ۱۹۱ کاراکتر در `ensureRedirect`:** مسیرهای فارسی percent-encoded خیلی بلندند و الان بی‌صدا رد می‌شوند. بررسی کن ستون‌های `from_path` و `to_path` جدول `cms_redirects` چه طولی دارند. اگر ۱۹۱ است، مایگریشن جدید بساز و به ۵۰۰ برسان (ایندکس یکتای ترکیبی موجود را در نظر بگیر؛ اگر روی MySQL با `utf8mb4` ایندکس از ۷۶۸ بایت بیشتر می‌شود، از ایندکس روی hash استفاده کن یا ایندکس prefix بگذار). سپس محدودیت داخل `ensureRedirect` را به همان طول برسان. موارد ردشده را لاگ کن (`Log::warning`)، نه اینکه بی‌صدا رد شوند.

**`wp:migrate-audit`:** در متد `audit()` دو ردیف اضافه کن: `wp_questions` (تعداد `qa` منتشرشده) و `local_questions` (تعداد `CafeQuestion` با `wp_post_id` غیر null).

### ۲-۶. کنترلر: `CafeQuestionController`

**`index`:**
- پارامتر صفحه را از route بخوان (`$request->route('page')`)؛ همان الگوی `BlogController::index` (merge در `page`).
- `paginate(20)` (مطابق وردپرس)، با `withQueryString()`، و `abort_if($questions->currentPage() > $questions->lastPage() && $questions->currentPage() > 1, 404)`.
- ترتیب پیش‌فرض (`sort = latest`): `answered_at desc`، سپس `id desc`. این همان ترتیب آرشیو وردپرس (تاریخ انتشار نزولی) است.
- لینک‌های صفحه‌بندی باید `/questions/page/{n}/` باشند، نه `?page=n`. از `pagination/blog.blade.php` و متغیر `paginationBase = '/questions'` استفاده کن، همان‌طور که بلاگ انجام می‌دهد.
- متا:
  - title: صفحه‌ی ۱: `کافه سوال راهبر حساب - راهبر حساب`. صفحه‌ی n: `کافه سوال راهبر حساب - صفحه {n} از {lastPage} - راهبر حساب` (اعداد لاتین، مثل وردپرس).
  - description و keywords فعلی بمانند.
  - canonical: `/questions/` یا `/questions/page/{n}/` (با `PermalinkService::absolute`).
  - اگر هر کدام از پارامترهای `q`، `sort` (غیر `latest`) یا `category` در درخواست بود: `'robots' => 'noindex, follow'`.
- breadcrumb: نام آیتم دوم `کافه سوال راهبر حساب`.

**`show`:**
- `->where('slug', $slug)` را مثل `BlogController::show` برای نامک‌های encoded هم امن کن (`orWhere` با `rawurldecode`).
- متا:
  - `title`: `$question->meta_title ?: 'پرسش و پاسخ : '.$question->title.' - راهبر حساب'`
  - `description`: `$question->meta_description ?: Str::limit(trim(strip_tags($question->body)), 160)`
  - `canonical`: `$question->canonical_url ?: PermalinkService::absolute('/questions/'.$question->slug)`
  - `robots`: `$question->robots`؛ اگر خالی است، `'index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large'`
  - `og_image`: `$question->og_image ?: $question->featured_image ?: $question->video_poster`
  - `og_type`: `'article'`
- breadcrumb: `خانه` ← `کافه سوال راهبر حساب` (`/questions/`) ← عنوان سؤال.

**`PermalinkService`:** متد `question(CafeQuestion $q): string` (خروجی `normalizePath('/questions/'.$q->slug)`) و `questionAbsolute()` اضافه کن. در کنترلر، ویو و سایت‌مپ از این استفاده کن، نه از ساختن دستی رشته.

**`store` و `storeAnswer`:** رفتار تغییر نمی‌کند. فقط مطمئن شو redirectها به آدرس جدید می‌روند (چون از `route()` استفاده می‌کنند، خودکار درست می‌شوند).

### ۲-۷. ویوها

**`resources/views/pages/cafe-soal/show.blade.php`:**
- `{{ $question->body }}` را به `{!! $question->bodyHtml() !!}` تغییر بده و کلاس `cafe-answer__body--rich` (یا معادلش برای سؤال) را اضافه کن تا HTML درست نمایش داده شود.
- اگر `video_url` دارد، زیر متن سؤال و داخل همان `<article>`:
  ```blade
  <video class="cafe-question__video" controls preload="none" playsinline @if($question->video_poster) poster="{{ $question->video_poster }}" @endif>
      <source src="{{ $question->video_url }}" type="video/mp4">
  </video>
  ```
  و CSS ساده‌ی واکنش‌گرا (`width: 100%; height: auto; border-radius` مثل بقیه‌ی کارت‌ها) در فایل CSS سایت که استایل‌های `cafe-` در آن است.
- پاسخ رسمی همان `clean_html` فعلی بماند.
- پاسخ‌های جامعه: نام از `authorDisplayName()`، متن از `bodyHtml()`. زیر هر پاسخ، `replies` با یک سطح تورفتگی (کلاس `cafe-answer--reply`) و با همان قالب.
- ساختار معنایی: یک `<h1>` (همین الان هست)، سؤال داخل `<article>`، پاسخ‌ها داخل `<section>` با `<h2>`. این را حفظ کن.

**`resources/views/pages/cafe-soal/index.blade.php`:**
- H1 صفحه باید `کافه سوال راهبر حساب` باشد.
- لینک هر سؤال با `route('cafe-soal.show', $slug)` ساخته می‌شود؛ تغییری لازم نیست.
- اگر تصویر کارت دارد، `alt` برابر عنوان سؤال باشد.

**منوی سایت (`components/navbar.blade.php`) و هر جای دیگری که `/cafe-soal` به‌صورت رشته‌ی ثابت نوشته شده:** با `route('cafe-soal.index')` جایگزین کن. با `rg -n "cafe-soal" resources app` همه را پیدا کن؛ فقط رشته‌های آدرس را عوض کن، نه نام routeها و کلاس‌های CSS.

### ۲-۸. سایت‌مپ

**`app/Services/SitemapService.php`:** متد `questions()` با همان الگوی `laws()`:
- اول `/questions/` (priority `0.8`، changefreq `daily`).
- سپس همه‌ی `CafeQuestion::published()` (با `select` محدود و `lazyById(250)`)، به‌جز آن‌هایی که robots آن‌ها noindex است. `loc` از `PermalinkService::questionAbsolute`، `lastmod` از `updated_at`، priority `0.6`، changefreq `monthly`.

**مسیرها و ایندکس:**
- route جدید `GET /qa-sitemap.xml` با نام `sitemap.questions`.
- آدرس‌های قدیمی Rank Math یعنی `/qa-sitemap1.xml` و `/qa-sitemap2.xml` با ۳۰۱ به `/qa-sitemap.xml` بروند.
- در `SeoService::sitemapIndex()` آیتم `/qa-sitemap.xml` را اضافه کن.
- نام‌های جدید (`qa-sitemap.xml`، `qa-sitemap1.xml`، `qa-sitemap2.xml`) را به `reserved_prefixes` در `config/seo.php` اضافه کن.

در `SitemapService::pages()` آیتم `'cafe-soal' => ['/cafe-soal', ...]` را حذف کن؛ آدرس `/questions/` حالا در `qa-sitemap.xml` است.

### ۲-۹. اسکیما

- breadcrumb همان‌طور که بالا گفته شد.
- `VideoObject` برای سؤال‌هایی که ویدیو دارند، در `SeoService::videoSchema(string $name, ?string $description, string $contentUrl, ?string $thumbnailUrl, ?Carbon $uploadDate): array`. فیلدها: `name`، `description`، `contentUrl`، `thumbnailUrl`، `uploadDate` (ISO 8601). فیلدهای خالی حذف شوند (`array_filter`). اگر `thumbnailUrl` نداریم، اسکیما را اصلاً نساز (گوگل آن را اجباری می‌داند).
- **`QAPage` اضافه نکن.** نسخه‌ی زنده آن را ندارد و سیاست فاز اول هم‌ارزی است.

### ۲-۱۰. تست‌ها

**`tests/Feature/CafeQuestionTest.php` و `tests/Feature/RouteSmokeTest.php`:** هر جا آدرس `/cafe-soal` به‌صورت رشته نوشته شده، به `/questions` تغییر بده. تست‌هایی که با `route(...)` کار می‌کنند باید بدون تغییر پاس شوند.

**تست جدید `tests/Feature/QuestionUrlParityTest.php`:**
- `/questions/` ۲۰۰ می‌دهد؛ H1 برابر `کافه سوال راهبر حساب`؛ canonical برابر `/questions/`.
- با ۲۵ سؤال منتشرشده: `/questions/page/2/` ۲۰۰ و ۵ مورد دارد؛ `/questions/page/3/` ۴۰۴ است؛ title صفحه‌ی ۲ شامل `صفحه 2 از 2` است.
- `/questions/{slug}/` ۲۰۰ می‌دهد؛ title برابر `پرسش و پاسخ : {عنوان} - راهبر حساب`؛ robots شامل `max-image-preview:large`.
- سؤال pending یا rejected روی `/questions/{slug}/` ۴۰۴ است.
- `/cafe-soal/` و `/cafe-soal/{slug}/` با ۳۰۱ به `/questions/...` می‌روند.
- `/questions/?q=x` روی robots `noindex` دارد.
- `qa-sitemap.xml` شامل آدرس سؤال منتشرشده است و سؤال noindex را ندارد؛ `sitemap_index.xml` شامل `qa-sitemap.xml` است.
- سؤال واردشده از وردپرس (`wp_post_id` دارد) HTML بدنه را رندر می‌کند؛ سؤال کاربر جدید با `<b>` در متن، escape می‌شود.
- اگر `video_url` دارد، تگ `<video>` و `VideoObject` در خروجی هست.

**تست جدید `tests/Feature/WordpressQuestionMigrationTest.php`** (الگو را از `tests/Feature/WordpressMigrationTest.php` بگیر؛ ببین آن تست چطور جدول‌های وردپرس را روی connection تست می‌سازد و همان را تکرار کن):
- یک پست `qa` با `_qa_answer`، `reza_video_url`، `rank_math_title`، دو دیدگاه (یکی پاسخ به دیگری)، یک `_wp_old_slug` و یک تگ بساز. بعد از `migrateQuestions()`:
  - سؤال با همان نامک و `status = answered` ساخته شده و `created_at` برابر `post_date` است.
  - یک پاسخ رسمی و دو دیدگاه ساخته شده‌اند و دیدگاه دوم `parent_id` دارد.
  - تگ متصل شده است.
  - ریدایرکت‌های `/?p={ID}` و `/questions/{old}` ساخته شده‌اند.
  - `meta_title` برابر `پرسش و پاسخ : {عنوان} - راهبر حساب` است.
- اجرای دوباره رکورد تکراری نمی‌سازد.
- پست `trash` منتقل نمی‌شود.
- اگر سؤالی با همان نامک و بدون `wp_post_id` از قبل باشد، بازنویسی نمی‌شود و در `conflicts` می‌آید.
- `--dry-run` چیزی نمی‌نویسد.

### ۲-۱۱. مستندات
- `docs/seo/URL-MAP.md`: ردیف‌های `/questions/`، `/questions/page/{n}/`، `/questions/{slug}/` با رفتار ۲۰۰ اضافه شوند. در بخش «مسیرهای کمکی لاراول» اشاره به `/cafe-soal/` را حذف کن و بنویس به `/questions/` ۳۰۱ می‌شود.
- `docs/seo/SEO-OVERVIEW.md`: جدول بخش ۲ (کافه سوال)، بخش ۸ (سایت‌مپ `qa-sitemap.xml`)، بخش ۱۰ (دستور `wp:migrate-questions` و ریدایرکت نامک‌های قدیمی)، بخش ۷ (`VideoObject`).

### معیار پذیرش تسک ۲
- [ ] `php artisan wp:migrate-questions --dry-run` عدد ۵۸۵ را نشان می‌دهد و تعارضی ندارد.
- [ ] بعد از اجرای واقعی، `wp:migrate-audit` برای `wp_questions` و `local_questions` هر دو ۵۸۵ را نشان می‌دهد.
- [ ] سه نمونه از `qa-sitemap1.xml` زنده روی Laravel با همان آدرس ۲۰۰ می‌دهند و title و H1 آن‌ها با نسخه‌ی زنده یکی است.
- [ ] `/questions/page/30/` ۲۰۰ و `/questions/page/31/` ۴۰۴ است.
- [ ] همه‌ی تست‌ها پاس می‌شوند.

---

## تسک ۳: ابزار مقایسه‌ی آدرس‌به‌آدرس و نقشه‌ی کامل URL

### هدف
سئوکار «نقشه‌ی نگاشت URL قدیمی به جدید» برای همه‌ی حدود ۱۰۰۰ صفحه خواسته. این ابزار:
1. همه‌ی آدرس‌های ایندکس‌پذیر وردپرس را جمع می‌کند.
2. هر آدرس را روی Laravel با **همان مسیر** درخواست می‌دهد.
3. گزارش می‌دهد کدام درست است و کدام نه.
4. یک فایل CSV تولید می‌کند که همان نقشه‌ی نگاشت است و در مخزن نگه‌داری می‌شود.

### ۳-۱. دستور

`app/Console/Commands/SeoCompareWpUrlsCommand.php`:
```
seo:compare-wp-urls
    {--sitemap=https://rahbarhesab.com/sitemap_index.xml : سایت‌مپ زنده‌ی وردپرس (یا مسیر فایل محلی)}
    {--with-db : آدرس‌های اضافه را از دیتابیس وردپرس هم جمع کن (دسته‌ها، تگ‌ها، صفحه‌بندی آرشیوها)}
    {--target= : اگر خالی باشد، درخواست‌ها درون برنامه (kernel) اجرا می‌شوند؛ اگر آدرس داده شود (مثلاً https://delicollection.ir) با HTTP واقعی}
    {--compare-live : عنوان، H1، description و canonical را با نسخه‌ی زنده‌ی وردپرس هم مقایسه کن}
    {--limit= : فقط N آدرس اول (برای تست سریع)}
    {--output=docs/seo/url-map.csv : مسیر فایل CSV خروجی}
```
منطق را در یک سرویس جدا بنویس، نه داخل دستور: `app/Services/Seo/UrlParityAuditor.php`. دستور فقط ورودی می‌گیرد، progress bar نشان می‌دهد و خلاصه چاپ می‌کند.

### ۳-۲. جمع‌آوری آدرس‌ها

**از سایت‌مپ (`--sitemap`):**
- اگر ورودی sitemap index است، همه‌ی زیرسایت‌مپ‌ها را بخوان. با `Http::timeout(30)->retry(2, 500)->get()` و `simplexml_load_string` (namespace سایت‌مپ را در نظر بگیر).
- `local-sitemap.xml` را رد کن (فقط `locations.kml` است). آن را جدا با نوع `kml` در گزارش بیاور.
- آدرس‌های `/wp-content/` (تصاویر داخل `image:image`) را نادیده بگیر؛ فقط `<url><loc>` را بردار.
- برای هر آدرس، نام سایت‌مپ منبع را نگه دار (ستون `source`).
- `video-sitemap.xml` تکراری مقاله‌هاست؛ آدرس‌ها را deduplicate کن ولی در ستون `source` هر دو منبع را بنویس (با `|` جدا شوند). این برای تسک‌های بعدی (اسکیمای ویدیو) لازم است.

**از دیتابیس وردپرس (`--with-db`):** این‌ها در سایت‌مپ نیستند ولی ممکن است ایندکس باشند:
- دسته‌های `category` که حداقل یک پست منتشرشده دارند: `/category/{slug}/` (اگر وردپرس base دسته را تغییر داده، از option `category_base` بخوان).
- تگ‌های `post_tag` که حداقل یک پست منتشرشده (`post` یا `qa`) دارند: `/tag/{slug}/`.
- دسته‌های `product_cat` با محصول منتشرشده: `/product-category/{slug}/`.
- صفحه‌بندی: برای `/blog/`، `/product/`، `/questions/` و هر دسته و تگ، تعداد صفحات را از `posts_per_page` حساب کن و `/page/{n}/` را برای n=2..آخر اضافه کن. دوره‌ها: ووکامرس از option `woocommerce_catalog_rows` × `woocommerce_catalog_columns` یا مقدار پیش‌فرض ۱۲ استفاده می‌کند؛ اگر option نبود، از Laravel (`CourseController`، ۱۲) استفاده کن و در گزارش بنویس `estimated`.
- ریدایرکت‌های وردپرس: همه‌ی `_wp_old_slug`های پست‌های منتشرشده با نوع `old-slug` و **انتظار ۳۰۱** (نه ۲۰۰).
- ستون `source` برای این‌ها `db:category`، `db:tag`، `db:product_cat`، `db:pagination` و `db:old-slug` باشد.

**نرمال‌سازی:** همه‌ی آدرس‌ها را به شکل مسیر (path) با encoding یکسان درآور. حروف hex در percent-encoding را **بزرگ** کن (`%d8` → `%D8`)، چون وردپرس کوچک می‌نویسد ولی PHP بزرگ. هنگام مقایسه، هر دو طرف را با همین قاعده نرمال کن.

### ۳-۳. درخواست به Laravel

**حالت kernel (پیش‌فرض، بدون `--target`):**
- برای هر مسیر `Request::create('https://rahbarhesab.com'.$path, 'GET')` بساز و با `app(\Illuminate\Contracts\Http\Kernel::class)->handle($request)` اجرا کن، سپس `terminate`.
- **قبل از شروع:** محیط را موقتاً production در نظر بگیر (`app()->detectEnvironment(fn () => 'production')`) تا متا و robots و canonical مثل production باشند. در پایان برگردان. نشست (session) و کش را بین درخواست‌ها پاک نکن، ولی `Auth::logout()` را مطمئن شو (درخواست‌ها مهمان باشند).
- **شمارنده‌ی بازدید:** این درخواست‌ها نباید `views` را بالا ببرند. کل اجرا را داخل `DB::beginTransaction()` و در پایان `DB::rollBack()` بگذار.
- از `Redirect` پیروی نکن؛ فقط وضعیت و `Location` را ثبت کن.

**حالت HTTP (`--target=https://delicollection.ir`):**
- دامنه را با target عوض کن، مسیر دست‌نخورده بماند.
- `Http::withOptions(['allow_redirects' => false])->timeout(20)` و `Http::pool` با حداکثر ۸ درخواست هم‌زمان.
- اگر استیج پسورد دارد، از env `SEO_AUDIT_BASIC_AUTH=user:pass` بخوان و با `withBasicAuth` بفرست.

### ۳-۴. بررسی هر آدرس

از HTML پاسخ با `DOMDocument` (با `libxml_use_internal_errors(true)` و تبدیل UTF-8 درست) این‌ها را استخراج کن: `title`، `meta[name=description]`، `link[rel=canonical]`، `meta[name=robots]`، تعداد و متن `h1`، تعداد `h2`، تعداد `<main>`، تعداد `<article>`، تعداد تصاویر بدون `alt` یا با `alt` خالی، تعداد لینک‌های داخلی (`a[href]` با مسیر نسبی یا دامنه‌ی `rahbarhesab.com`).

**وضعیت هر آدرس (`result`):**
- `ok`: کد ۲۰۰، canonical برابر `https://rahbarhesab.com{همان مسیر}` (بعد از نرمال‌سازی)، دقیقاً یک `h1`، title غیرخالی.
- `ok-redirect`: برای `old-slug` و آدرس‌هایی که در جدول `cms_redirects` ریدایرکت دارند: ۳۰۱ با **یک پرش** به مقصدی که خودش `ok` است. مقصد را هم بررسی کن.
- `missing`: کد ۴۰۴ یا ۴۱۰.
- `redirected`: ۳۰۱ یا ۳۰۲ برای آدرسی که باید ۲۰۰ باشد. در `notes` مقصد را بنویس.
- `error`: کد ۵xx یا exception (پیام exception در `notes`).
- `warn`: ۲۰۰ است ولی canonical فرق دارد، یا `h1` صفر یا بیشتر از یک است، یا title خالی است، یا robots شامل `noindex` است در حالی که وردپرس `index` بود (فقط با `--compare-live` قابل تشخیص است؛ بدون آن، هر `noindex` را `warn` کن).

**با `--compare-live`:** همان آدرس را از وردپرس زنده هم بگیر (با همان pool و `allow_redirects => false`). ستون‌های `wp_title`، `wp_h1`، `wp_canonical`، `wp_robots`، `wp_h2_count` و `wp_internal_links` را پر کن. اگر title یا H1 یا canonical (بعد از تبدیل دامنه) فرق داشت، `result` را `warn` کن و در `notes` بنویس چه فرق کرده. اختلاف تعداد لینک داخلی بیش از ۳۰٪ هم `warn` است. سرعت درخواست به وردپرس را محدود کن (حداکثر ۴ هم‌زمان) تا سرور زنده اذیت نشود.

### ۳-۵. خروجی

**CSV (`--output`، پیش‌فرض `docs/seo/url-map.csv`):** با BOM برای UTF-8 تا Excel فارسی را درست نشان دهد. ستون‌ها به این ترتیب:
```
old_url,new_url,source,expected,status,location,result,canonical,robots,title,h1,h1_count,h2_count,main_count,article_count,images_missing_alt,internal_links,wp_title,wp_h1,wp_canonical,wp_robots,wp_h2_count,wp_internal_links,notes
```
- `old_url`: آدرس کامل وردپرس (`https://rahbarhesab.com/...`).
- `new_url`: همان مسیر روی دامنه‌ی target (یا `https://rahbarhesab.com` در حالت kernel). برای `ok-redirect`، مقصد نهایی.
- `expected`: `200` یا `301`.
- مرتب‌سازی: اول `missing` و `error`، بعد `redirected`، بعد `warn`، بعد `ok`. داخل هر گروه بر اساس `source` و `old_url`.

**خلاصه در کنسول:** جدول تعداد هر `result` به تفکیک `source`، و ۲۰ مورد اول `missing` و `error`.

**کد خروج:** اگر `missing`، `error` یا `redirected` بیشتر از صفر بود، `FAILURE`؛ وگرنه `SUCCESS`. (برای استفاده در CI قبل از لانچ.)

### ۳-۶. تست‌ها

`tests/Feature/SeoCompareWpUrlsCommandTest.php`:
- با `Http::fake` یک sitemap index با دو زیرسایت‌مپ شبیه‌سازی کن: یک مقاله که در Laravel وجود دارد، یک آدرس `/questions/{slug}/` که وجود ندارد، و یک آدرس که percent-encoding با حروف کوچک دارد. در حالت kernel اجرا کن و بررسی کن:
  - CSV ساخته شده و ردیف مقاله `ok` است.
  - ردیف سؤال `missing` است.
  - آدرس با حروف کوچک hex به درستی match شده است.
  - کد خروج `FAILURE` است.
- با `--limit=1` فقط یک ردیف بررسی می‌شود.
- آدرس‌های `/wp-content/` و `locations.kml` در ستون `result` با `ok` شمرده نمی‌شوند (اولی نادیده گرفته می‌شود و دومی نوع `kml` دارد).
- اجرای دستور تعداد `views` هیچ مقاله‌ای را تغییر نمی‌دهد.
- در حالت `--target`، درخواست‌ها با `allow_redirects = false` به دامنه‌ی target فرستاده می‌شوند (با `Http::assertSent`).
- (اگر `--with-db` قابل تست است، با الگوی `WordpressMigrationTest` جدول‌های وردپرس را بساز و بررسی کن یک دسته‌ی دارای پست و صفحه‌بندی آن به لیست اضافه می‌شوند.)

### ۳-۷. اجرای واقعی و تحویل
1. بعد از اجرای تسک ۱ و ۲ و مهاجرت کامل محتوا روی دیتابیس محلی، اجرا کن:
   `php artisan seo:compare-wp-urls --with-db`
2. خروجی `docs/seo/url-map.csv` را commit کن؛ این همان «نقشه‌ی نگاشت URL» است که سئوکار خواسته.
3. یک فایل `docs/seo/URL-PARITY-REPORT.md` با این محتوا بساز:
   - تاریخ اجرا و دستور دقیق
   - جدول خلاصه (تعداد هر `result` به تفکیک `source`)
   - لیست کامل `missing`، `error` و `redirected` با علت احتمالی هر کدام (مثلاً «برگه در وردپرس هست ولی منتقل نشده»، «نوع محتوا در Laravel مسیر ندارد»)
   - لیست `warn`ها گروه‌بندی‌شده بر اساس نوع مشکل (canonical، H1، title، alt، robots)
   
   **مشکلات پیدا شده را در این تسک اصلاح نکن**؛ فقط گزارش کن. اصلاح آن‌ها تسک جداست.
4. در `docs/seo/LAUNCH-GATE.md`، بخش «URL و وضعیت HTTP»، یک بند اضافه کن: «`php artisan seo:compare-wp-urls --with-db --target={staging}` بدون `missing`، `error` و `redirected` تمام می‌شود.»
5. در `docs/seo/SEO-OVERVIEW.md`، بخش ابزارها، دستور جدید و کاربردش را توضیح بده.

### معیار پذیرش تسک ۳
- [ ] دستور روی سایت‌مپ زنده حدود ۸۲۰ آدرس (بدون تکراری‌ها) و با `--with-db` بیشتر جمع می‌کند.
- [ ] `docs/seo/url-map.csv` و `docs/seo/URL-PARITY-REPORT.md` ساخته و commit شده‌اند.
- [ ] اجرای دستور هیچ داده‌ای در دیتابیس Laravel تغییر نمی‌دهد (شمارنده‌ی بازدید هم نه).
- [ ] همه‌ی تست‌ها پاس می‌شوند.

---

## خارج از محدوده‌ی این پلن (کار نکن)

- تغییر ظاهر و UI صفحات، به‌جز چیزهایی که بالا صریحاً گفته شده (ویدیو و پاسخ‌های تودرتو در صفحه‌ی سؤال).
- اضافه کردن H1 به صفحه‌ی اصلی و `alt` به تصاویر کارت‌ها در لیست‌ها. این‌ها در گزارش تسک ۳ می‌آیند و تسک جدا دارند.
- مهاجرت `law_library` (۳ مورد)، `wps_cpt_membership` و صفحه‌های نویسنده. اگر تسک ۳ آن‌ها را `missing` نشان داد، فقط در گزارش بیاور.
- اسکیمای `VideoObject` برای مقاله‌های بلاگ (`video-sitemap.xml`). در گزارش تسک ۳ لیستشان را بیاور.
- `QAPage` schema.
