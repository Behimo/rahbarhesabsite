@extends('layouts.admin')

@section('title', 'تنظیمات دیدگاه‌ها')

@section('heading', 'تنظیمات دیدگاه‌ها')

@section('lede', 'از اینجا مشخص کنید دیدگاه‌ها کجا فعال باشند، قبل از نمایش تأیید شوند یا نه، و با اسپم/الفاظ رکیک چطور برخورد شود.')

@section('actions')
    <a href="{{ route('admin.comments.index') }}" class="btn btn-label-secondary">بازگشت به لیست</a>
@endsection

@section('content')
@php
    $s = $settings;
    $lines = function (string $key) use ($s): array {
        $value = $s[$key] ?? [];
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value) ?: [];
        }

        return array_values(array_filter(array_map('trim', is_array($value) ? $value : [])));
    };
    $actionOptions = [
        'reject' => 'رد فوری (به کاربر خطا نشان داده می‌شود)',
        'spam' => 'علامت اسپم (در سایت دیده نمی‌شود، در پنل اسپم می‌ماند)',
        'pending' => 'رفتن به صف تأیید ادمین',
    ];
    $listFromOldOrStored = function (string $key) use ($lines): array {
        $old = old($key);
        if (is_array($old)) {
            return array_values(array_filter(array_map('trim', $old)));
        }
        if (is_string($old)) {
            return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $old) ?: [])));
        }

        return $lines($key);
    };
    $managed = (bool) ($s['word_lists_managed'] ?? false);
    $dictionary = config('comment_dictionaries', []);
    $editorItems = function (string $key) use ($managed, $listFromOldOrStored, $dictionary): array {
        if (old($key) !== null) {
            return $listFromOldOrStored($key);
        }

        $stored = $listFromOldOrStored($key);

        if ($stored !== [] || $managed) {
            return $stored;
        }

        return array_values(array_filter($dictionary[$key] ?? []));
    };
@endphp

<style>
    .comment-setting {
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        padding: 1rem 1.1rem;
        height: auto;
        background: var(--bs-body-bg);
    }
    .row > [class*="col-"] > .comment-setting:only-child {
        height: 100%;
    }
    .comment-setting__title {
        display: block;
        font-weight: 600;
        margin-bottom: .25rem;
    }
    .comment-setting__help {
        display: block;
        color: var(--bs-secondary-color);
        font-size: .8125rem;
        line-height: 1.6;
        margin: 0;
    }
    .comment-setting .form-check-input {
        margin-top: .2rem;
    }
    .comment-list-editor {
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        padding: .85rem;
        background: var(--bs-body-bg);
    }
    .comment-list-editor__chips {
        display: flex;
        flex-wrap: wrap;
        align-content: flex-start;
        gap: .4rem;
        min-height: 7rem;
        max-height: 16rem;
        margin-bottom: .75rem;
        padding: .35rem;
        overflow: auto;
        border-radius: .6rem;
        background: rgba(67, 89, 113, .04);
    }
    .comment-list-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        max-width: 100%;
        padding: .25rem .55rem;
        border-radius: 999px;
        background: rgba(105, 108, 255, .12);
        color: #696cff;
        font-size: .8125rem;
        line-height: 1.4;
    }
    .comment-list-chip code {
        direction: ltr;
        unicode-bidi: plaintext;
        background: transparent;
        color: inherit;
        font-size: inherit;
    }
    .comment-list-chip button {
        border: 0;
        background: transparent;
        color: inherit;
        opacity: .7;
        padding: 0;
        line-height: 1;
        font-size: 1rem;
    }
    .comment-list-chip button:hover { opacity: 1; }
    .comment-list-editor__empty {
        color: var(--bs-secondary-color);
        font-size: .8125rem;
        padding: .35rem .1rem;
    }
    .comment-list-editor__add {
        display: flex;
        gap: .5rem;
    }
    .comment-settings-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.25rem;
        margin-bottom: 2rem;
        padding: 1rem 1.1rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        background: var(--bs-body-bg);
    }
</style>

<form method="POST" action="{{ route('admin.comments.settings.update') }}" id="comment-settings-form">
    @csrf
    @method('PUT')

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-1">۱. روشن و خاموش بودن دیدگاه‌ها</h5>
            <p class="mb-0 text-muted small">اول مشخص کنید اصلاً سیستم دیدگاه فعال باشد یا نه، و روی چه محتواهایی کار کند.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled" @checked(old('enabled', $s['enabled'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="enabled">فعال‌سازی کلی</label>
                        </div>
                        <p class="comment-setting__help">اگر خاموش باشد، هیچ‌جا فرم دیدگاه نشان داده نمی‌شود و ارسال جدید ممکن نیست.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="types[cms_post]" value="1" id="type_post" @checked(old('types.cms_post', $s['types']['cms_post'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="type_post">روی مقالات</label>
                        </div>
                        <p class="comment-setting__help">دیدگاه زیر صفحات مقالات بلاگ نمایش داده شود.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="types[shop_product]" value="1" id="type_product" @checked(old('types.shop_product', $s['types']['shop_product'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="type_product">روی دوره‌ها</label>
                        </div>
                        <p class="comment-setting__help">دیدگاه در صفحه جزئیات دوره (`/product/...`) نمایش داده شود.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="default_status">وضعیت پیش‌فرض برای محتواهای جدید</label>
                    <select name="default_status" id="default_status" class="form-select">
                        <option value="open" @selected(old('default_status', $s['default_status'] ?? 'open') === 'open')>باز — کاربران می‌توانند دیدگاه بگذارند</option>
                        <option value="closed" @selected(old('default_status', $s['default_status'] ?? '') === 'closed')>بسته — تا وقتی برای همان محتوا باز نکنید، ارسال ممکن نیست</option>
                    </select>
                    <div class="form-text">فقط وقتی روی خود مقاله/دوره گزینه «پیروی از تنظیمات» انتخاب شده باشد اثر دارد.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="auto_close_days">بستن خودکار بعد از انتشار</label>
                    <div class="input-group">
                        <input type="number" min="0" max="3650" name="auto_close_days" id="auto_close_days" class="form-control" value="{{ old('auto_close_days', $s['auto_close_days'] ?? 0) }}">
                        <span class="input-group-text">روز</span>
                    </div>
                    <div class="form-text">۰ یعنی خاموش. برای مقالات از تاریخ انتشار و برای دوره‌ها از تاریخ ایجاد حساب می‌شود.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-1">۲. تأیید و نحوه ارسال</h5>
            <p class="mb-0 text-muted small">مشخص کنید دیدگاه‌ها بلافاصله منتشر شوند یا اول در صف تأیید بیایند، و چه کسانی بتوانند بنویسند.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="require_approval" value="1" id="require_approval" @checked(old('require_approval', $s['require_approval'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="require_approval">تأیید ادمین قبل از نمایش</label>
                        </div>
                        <p class="comment-setting__help">روشن: دیدگاه جدید اول «در انتظار» می‌ماند. خاموش: بعد از عبور از فیلترها، فوری منتشر می‌شود.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="auto_approve_known_users" value="1" id="auto_approve_known_users" @checked(old('auto_approve_known_users', $s['auto_approve_known_users'] ?? false))>
                            <label class="form-check-label comment-setting__title" for="auto_approve_known_users">تأیید خودکار کاربران سابقه‌دار</label>
                        </div>
                        <p class="comment-setting__help">اگر کاربر قبلاً حداقل یک دیدگاه تأییدشده داشته باشد، دیدگاه‌های بعدی‌اش بدون صف منتشر می‌شود.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="links_force_moderation" value="1" id="links_force_moderation" @checked(old('links_force_moderation', $s['links_force_moderation'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="links_force_moderation">لینک‌دارها به صف تأیید بروند</label>
                        </div>
                        <p class="comment-setting__help">حتی اگر تأیید خودکار روشن باشد، متن دارای لینک برای بررسی ادمین نگه داشته می‌شود.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="auth_required" value="1" id="auth_required" @checked(old('auth_required', $s['auth_required'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="auth_required">فقط کاربران واردشده</label>
                        </div>
                        <p class="comment-setting__help">روشن باشد تا مهمان نتواند دیدگاه بفرستد (پیشنهادی برای کاهش اسپم).</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="guests_allowed" value="1" id="guests_allowed" @checked(old('guests_allowed', $s['guests_allowed'] ?? false))>
                            <label class="form-check-label comment-setting__title" for="guests_allowed">اجازه ارسال مهمان</label>
                        </div>
                        <p class="comment-setting__help">فقط وقتی «فقط کاربران واردشده» خاموش باشد معنی دارد؛ مهمان باید نام و ایمیل وارد کند.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="allow_replies" value="1" id="allow_replies" @checked(old('allow_replies', $s['allow_replies'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="allow_replies">اجازه پاسخ به دیدگاه</label>
                        </div>
                        <p class="comment-setting__help">کاربران بتوانند زیر یک دیدگاه، پاسخ بگذارند.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="max_depth">حداکثر عمق پاسخ</label>
                    <input type="number" min="1" max="3" name="max_depth" id="max_depth" class="form-control" value="{{ old('max_depth', $s['max_depth'] ?? 1) }}">
                    <div class="form-text">۱ یعنی فقط پاسخ به دیدگاه اصلی؛ پاسخ به پاسخ مجاز نیست.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="min_length">حداقل طول متن</label>
                    <div class="input-group">
                        <input type="number" min="1" max="500" name="min_length" id="min_length" class="form-control" value="{{ old('min_length', $s['min_length'] ?? 3) }}">
                        <span class="input-group-text">کاراکتر</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="max_length">حداکثر طول متن</label>
                    <div class="input-group">
                        <input type="number" min="10" max="10000" name="max_length" id="max_length" class="form-control" value="{{ old('max_length', $s['max_length'] ?? 2000) }}">
                        <span class="input-group-text">کاراکتر</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="edit_window_minutes">مهلت ویرایش توسط نویسنده</label>
                    <div class="input-group">
                        <input type="number" min="0" max="10080" name="edit_window_minutes" id="edit_window_minutes" class="form-control" value="{{ old('edit_window_minutes', $s['edit_window_minutes'] ?? 0) }}">
                        <span class="input-group-text">دقیقه</span>
                    </div>
                    <div class="form-text">۰ یعنی ویرایش توسط کاربر غیرفعال است.</div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="allow_delete_own" value="1" id="allow_delete_own" @checked(old('allow_delete_own', $s['allow_delete_own'] ?? false))>
                            <label class="form-check-label comment-setting__title" for="allow_delete_own">حذف دیدگاه توسط خود کاربر</label>
                        </div>
                        <p class="comment-setting__help">اگر روشن باشد، نویسنده می‌تواند دیدگاه خودش را حذف کند.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-1">۳. نمایش در سایت</h5>
            <p class="mb-0 text-muted small">ترتیب لیست، تعداد در هر صفحه، و متن‌هایی که کاربر می‌بیند.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="order">ترتیب نمایش دیدگاه‌ها</label>
                    <select name="order" id="order" class="form-select">
                        <option value="newest" @selected(old('order', $s['order'] ?? 'newest') === 'newest')>جدیدترین بالا</option>
                        <option value="oldest" @selected(old('order', $s['order'] ?? '') === 'oldest')>قدیمی‌ترین بالا</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="per_page">تعداد در هر صفحه</label>
                    <input type="number" min="5" max="100" name="per_page" id="per_page" class="form-control" value="{{ old('per_page', $s['per_page'] ?? 20) }}">
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="show_count_on_listings" value="1" id="show_count_on_listings" @checked(old('show_count_on_listings', $s['show_count_on_listings'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="show_count_on_listings">نمایش تعداد در لیست مقالات</label>
                        </div>
                        <p class="comment-setting__help">روی کارت‌های آرشیو بلاگ، تعداد دیدگاه تأییدشده نشان داده شود.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="closed_message">پیام وقتی دیدگاه‌ها بسته‌اند</label>
                    <input type="text" name="closed_message" id="closed_message" class="form-control" value="{{ old('closed_message', $s['closed_message'] ?? '') }}">
                    <div class="form-text">به‌جای فرم ارسال، این متن نشان داده می‌شود.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="pending_message">پیام بعد از ارسال موفق (در صف)</label>
                    <input type="text" name="pending_message" id="pending_message" class="form-control" value="{{ old('pending_message', $s['pending_message'] ?? '') }}">
                    <div class="form-text">وقتی دیدگاه ثبت شد ولی هنوز تأیید نشده.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="rejected_message">پیام رد محتوا</label>
                    <input type="text" name="rejected_message" id="rejected_message" class="form-control" value="{{ old('rejected_message', $s['rejected_message'] ?? '') }}">
                    <div class="form-text">وقتی متن به‌خاطر قوانین (مثل رکیک) پذیرفته نشود.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-1">۴. ضد‌اسپم و ایمنی</h5>
            <p class="mb-0 text-muted small">محدودیت نرخ ارسال، تشخیص ربات، لینک زیاد و تکرار مشکوک.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="throttle_per_minute">سقف ارسال در هر دقیقه</label>
                    <input type="number" min="1" max="60" name="throttle_per_minute" id="throttle_per_minute" class="form-control" value="{{ old('throttle_per_minute', $s['throttle_per_minute'] ?? 3) }}">
                    <div class="form-text">برای هر کاربر یا هر IP؛ از ارسال پشت‌سرهم جلوگیری می‌کند.</div>
                </div>
                <div class="col-md-4">
                    <div class="comment-setting">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="honeypot_enabled" value="1" id="honeypot_enabled" @checked(old('honeypot_enabled', $s['honeypot_enabled'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="honeypot_enabled">تله ضد‌ربات (Honeypot)</label>
                        </div>
                        <p class="comment-setting__help">یک فیلد مخفی در فرم می‌گذارد؛ اگر ربات آن را پر کند، دیدگاه به‌عنوان اسپم ذخیره می‌شود.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="max_links">حداکثر تعداد لینک در یک دیدگاه</label>
                    <input type="number" min="0" max="20" name="max_links" id="max_links" class="form-control" value="{{ old('max_links', $s['max_links'] ?? 1) }}">
                    <div class="form-text">۰ یعنی هیچ لینکی مجاز نیست.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="block_on_max_links">اگر لینک‌ها از سقف بیشتر شد</label>
                    <select name="block_on_max_links" id="block_on_max_links" class="form-select">
                        @foreach ($actionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('block_on_max_links', $s['block_on_max_links'] ?? 'spam') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="duplicate_window_minutes">جلوگیری از متن تکراری</label>
                    <div class="input-group">
                        <input type="number" min="0" max="1440" name="duplicate_window_minutes" id="duplicate_window_minutes" class="form-control" value="{{ old('duplicate_window_minutes', $s['duplicate_window_minutes'] ?? 30) }}">
                        <span class="input-group-text">دقیقه</span>
                    </div>
                    <div class="form-text">همان کاربر/IP نتواند متن کاملاً یکسان را دوباره بفرستد. ۰ = خاموش.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="max_repeat_chars">سقف تکرار پشت‌سرهم یک کاراکتر</label>
                    <input type="number" min="0" max="50" name="max_repeat_chars" id="max_repeat_chars" class="form-control" value="{{ old('max_repeat_chars', $s['max_repeat_chars'] ?? 5) }}">
                    <div class="form-text">مثلاً ۵ یعنی «!!!!!» مشکوک حساب می‌شود و امتیاز اسپم می‌گیرد.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="soft_score_threshold">آستانه امتیاز مشکوک</label>
                    <input type="number" min="1" max="100" name="soft_score_threshold" id="soft_score_threshold" class="form-control" value="{{ old('soft_score_threshold', $s['soft_score_threshold'] ?? 30) }}">
                    <div class="form-text">اگر مجموع نشانه‌های مشکوک به این عدد برسد، حتی بدون رد قطعی به صف تأیید می‌رود.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-0">
        <div class="card-header">
            <h5 class="mb-1">۵. کلمات ممنوع، هرزنامه و IP مسدود</h5>
            <p class="mb-0 text-muted small">لیست الفاظ رکیک و هرزنامه از واژه‌نامه داخلی برنامه پر شده است. تا قبل از اولین ذخیره، همان واژه‌نامه روی دیدگاه‌ها اعمال می‌شود. بعد از ذخیره، فقط همین فهرست ویرایش‌شده ملاک است.</p>
        </div>
        <div class="card-body">
            <div class="alert alert-secondary mb-4" role="note">
                منبع لیست‌ها: واژه‌نامه داخلی در تنظیمات سیستم. کلمه را بنویسید و Enter بزنید. برای حذف، × را بزنید. اگر لیست را خالی ذخیره کنید، آن فیلتر دیگر کلمه‌ای ندارد.
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="comment-setting mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="profanity_filter_enabled" value="1" id="profanity_filter_enabled" @checked(old('profanity_filter_enabled', $s['profanity_filter_enabled'] ?? true))>
                            <label class="form-check-label comment-setting__title" for="profanity_filter_enabled">فیلتر الفاظ رکیک</label>
                        </div>
                        <p class="comment-setting__help">متن دیدگاه با همین فهرست مقایسه می‌شود. ی/ک عربی، نیم‌فاصله و جداکننده مثل نقطه هم در نظر گرفته می‌شود.</p>
                    </div>
                    <label class="form-label" for="profanity_action">اگر کلمه رکیک پیدا شد</label>
                    <select name="profanity_action" id="profanity_action" class="form-select mb-3">
                        @foreach ($actionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('profanity_action', $s['profanity_action'] ?? 'reject') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">الفاظ رکیک</label>
                        <button type="button" class="btn btn-sm btn-label-secondary" data-restore-list="profanity_words">بازگردانی پیش‌فرض</button>
                    </div>
                    <div class="comment-list-editor" data-list-editor data-name="profanity_words" data-placeholder="کلمه جدید" data-empty="واژه‌ای در فهرست نیست.">
                        <textarea class="d-none" name="profanity_words">{{ implode("\n", $editorItems('profanity_words')) }}</textarea>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <span class="comment-setting__title d-block mb-1">هرزنامه و تبلیغ</span>
                        <p class="comment-setting__help">کلمات تبلیغاتی مثل قمار، وام فوری یا درآمد اینترنتی. این‌ها فحش نیستند؛ جدا از لیست رکیک بررسی می‌شوند.</p>
                    </div>
                    <label class="form-label" for="blacklist_action">اگر کلمه هرزنامه پیدا شد</label>
                    <select name="blacklist_action" id="blacklist_action" class="form-select mb-3">
                        @foreach ($actionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('blacklist_action', $s['blacklist_action'] ?? 'spam') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">کلمات هرزنامه</label>
                        <button type="button" class="btn btn-sm btn-label-secondary" data-restore-list="blacklist_words">بازگردانی پیش‌فرض</button>
                    </div>
                    <div class="comment-list-editor" data-list-editor data-name="blacklist_words" data-placeholder="عبارت جدید" data-empty="عبارتی در فهرست نیست.">
                        <textarea class="d-none" name="blacklist_words">{{ implode("\n", $editorItems('blacklist_words')) }}</textarea>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <span class="comment-setting__title d-block mb-1">IPهای مسدود</span>
                        <p class="comment-setting__help">این نشانی‌ها نمی‌توانند دیدگاه بفرستند. از صفحه مدیریت دیدگاه‌ها هم می‌توانید IP یک دیدگاه را به این فهرست اضافه کنید. واژه‌نامه پیش‌فرض ندارد.</p>
                    </div>
                    <label class="form-label d-block mb-2">نشانی‌های مسدود</label>
                    <div class="comment-list-editor" data-list-editor data-name="blocked_ips" data-placeholder="مثلاً 203.0.113.10" data-dir="ltr" data-empty="هیچ IP مسدودی ثبت نشده.">
                        <textarea class="d-none" name="blocked_ips" dir="ltr">{{ implode("\n", $editorItems('blocked_ips')) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="comment-settings-actions">
        <span class="text-muted small">ذخیره، فهرست فعلی را جایگزین واژه‌نامه می‌کند و از این به بعد فقط همین موارد اعمال می‌شوند.</span>
        <button type="submit" class="btn btn-primary">ذخیره تنظیمات دیدگاه‌ها</button>
    </div>
</form>

<script type="application/json" id="comment-dictionary-defaults">@json(['profanity_words' => array_values($dictionary['profanity_words'] ?? []), 'blacklist_words' => array_values($dictionary['blacklist_words'] ?? [])])</script>
@endsection

@section('page-script')
<script>
(() => {
    const defaultsNode = document.getElementById('comment-dictionary-defaults');
    let defaults = {};
    try {
        defaults = JSON.parse(defaultsNode?.textContent || '{}');
    } catch (e) {
        defaults = {};
    }

    const editors = new Map();

    document.querySelectorAll('[data-list-editor]').forEach((root) => {
        const name = root.dataset.name;
        const placeholder = root.dataset.placeholder || 'مورد جدید';
        const emptyText = root.dataset.empty || 'موردی نیست.';
        const dir = root.dataset.dir || 'rtl';
        const hidden = root.querySelector('textarea');
        let items = (hidden.value || '').split(/\r\n|\r|\n/).map((item) => item.trim()).filter(Boolean);

        const meta = document.createElement('div');
        meta.className = 'comment-setting__help mb-2';
        root.prepend(meta);

        const chips = document.createElement('div');
        chips.className = 'comment-list-editor__chips';
        root.appendChild(chips);

        const addRow = document.createElement('div');
        addRow.className = 'comment-list-editor__add';
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control';
        input.placeholder = placeholder;
        input.dir = dir;
        input.autocomplete = 'off';
        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'btn btn-label-primary text-nowrap';
        addBtn.textContent = 'افزودن';
        addRow.append(input, addBtn);
        root.appendChild(addRow);

        const sync = () => {
            hidden.value = items.join('\n');
            meta.textContent = items.length
                ? `${items.length.toLocaleString('fa-IR')} مورد در این فهرست`
                : 'این فهرست خالی است.';
            chips.replaceChildren();

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'comment-list-editor__empty';
                empty.textContent = emptyText;
                chips.appendChild(empty);
                return;
            }

            items.forEach((value, index) => {
                const chip = document.createElement('span');
                chip.className = 'comment-list-chip';
                const code = document.createElement('code');
                code.textContent = value;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', 'حذف');
                remove.textContent = '×';
                remove.addEventListener('click', () => {
                    items.splice(index, 1);
                    sync();
                });
                chip.append(code, remove);
                chips.appendChild(chip);
            });
        };

        const addItem = () => {
            const value = input.value.trim();
            if (!value || items.includes(value)) {
                input.value = '';
                return;
            }
            items.push(value);
            input.value = '';
            sync();
            input.focus();
        };

        addBtn.addEventListener('click', addItem);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addItem();
            }
        });

        editors.set(name, {
            reset(next) {
                items = [...next];
                sync();
            },
        });
        sync();
    });

    document.querySelectorAll('[data-restore-list]').forEach((button) => {
        button.addEventListener('click', () => {
            const editor = editors.get(button.dataset.restoreList);
            if (!editor) return;
            editor.reset(defaults[button.dataset.restoreList] || []);
        });
    });
})();
</script>
@endsection
