@extends('layouts.site')

@php
    $askLoginUrl = route('login', ['redirect' => route('cafe-soal.index', ['ask' => 1])]);
@endphp

@section('page-banner')
<div class="cafe-hero" aria-labelledby="cafeHeroTitle">
    <div class="cafe-hero__atmosphere" aria-hidden="true">
        <span class="cafe-hero__glow"></span>
        <span class="cafe-hero__steam cafe-hero__steam--a"></span>
        <span class="cafe-hero__steam cafe-hero__steam--b"></span>
        <span class="cafe-hero__steam cafe-hero__steam--c"></span>
        <svg class="cafe-hero__cup" viewBox="0 0 120 88" fill="none">
            <path d="M18 28h68c4 0 7 3 7 7v18c0 14-12 26-26 26H37C23 79 11 67 11 53V35c0-4 3-7 7-7z" stroke="currentColor" stroke-width="2.2"/>
            <path d="M93 34h8c7 0 12 5 12 12s-5 12-12 12h-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M8 84h90" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity=".45"/>
        </svg>
    </div>

    <div class="cafe-hero__inner">
        <p class="cafe-hero__brand">راهبر حساب</p>
        <h1 id="cafeHeroTitle" class="cafe-hero__title">کافه سوال</h1>
        <p class="cafe-hero__lead">پاسخ دقیق به سوال‌های حسابداری، مالیات و امور مالی — از کارشناسان راهبر حساب</p>

        @auth
            <button type="button" class="cafe-ask-btn" data-cafe-ask-open>
                <span class="cafe-ask-btn__glow" aria-hidden="true"></span>
                <span class="cafe-ask-btn__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3c-4.4 0-8 3.1-8 7 0 2.3 1.2 4.4 3.1 5.7L6 20l4.1-2.1c.6.1 1.2.2 1.9.2 4.4 0 8-3.1 8-7s-3.6-7-8-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M9.5 10.5h5M12 8v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="cafe-ask-btn__label">سوال بپرسید</span>
            </button>
        @else
            <a href="{{ $askLoginUrl }}" class="cafe-ask-btn">
                <span class="cafe-ask-btn__glow" aria-hidden="true"></span>
                <span class="cafe-ask-btn__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3c-4.4 0-8 3.1-8 7 0 2.3 1.2 4.4 3.1 5.7L6 20l4.1-2.1c.6.1 1.2.2 1.9.2 4.4 0 8-3.1 8-7s-3.6-7-8-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M9.5 10.5h5M12 8v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="cafe-ask-btn__label">سوال بپرسید</span>
            </a>
        @endauth
    </div>
</div>
@endsection

@section('page')
<div class="cafe-page">
    <div class="cafe-layout{{ $cafeCategories->isNotEmpty() ? ' cafe-layout--with-side' : '' }}">
        @if ($cafeCategories->isNotEmpty())
            <aside class="cafe-side" aria-label="دسته‌بندی سوال‌ها">
                <div class="cafe-side__card">
                    <h2 class="cafe-side__title">دسته‌بندی</h2>
                    <nav class="cafe-side__nav">
                        <a href="{{ route('cafe-soal.index', array_filter(['q' => $q ?: null, 'sort' => $sort !== 'latest' ? $sort : null])) }}"
                           class="cafe-side__link{{ ! $categoryId ? ' is-active' : '' }}">
                            همه سوال‌ها
                        </a>
                        @foreach ($cafeCategories as $cat)
                            <a href="{{ route('cafe-soal.index', array_filter(['q' => $q ?: null, 'sort' => $sort !== 'latest' ? $sort : null, 'category' => $cat->id])) }}"
                               class="cafe-side__link{{ $categoryId === $cat->id ? ' is-active' : '' }}"
                               style="--cafe-depth: {{ $cat->treeDepth }}">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>
        @endif

        <div class="cafe-shell cafe-shell--main">
            @if (session('success'))
                <div class="cafe-flash cafe-flash--ok" role="status">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('cafe-soal.index') }}" class="cafe-toolbar" role="search">
                @if ($categoryId)
                    <input type="hidden" name="category" value="{{ $categoryId }}">
                @endif
                @if ($sort !== 'latest')
                    <input type="hidden" name="sort" value="{{ $sort }}">
                @endif
                <div class="cafe-toolbar__search">
                    <label class="cafe-sr-only" for="cafe-q">جستجوی سوال</label>
                    <span class="cafe-toolbar__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input type="search" name="q" id="cafe-q" value="{{ $q }}" placeholder="جستجو بین سوالات حسابداری و مالیات..." class="cafe-toolbar__input">
                    <button type="submit" class="cafe-toolbar__submit">جستجو</button>
                </div>
            </form>

            <div class="cafe-list-head">
                <p class="cafe-list-head__count">
                    @if ($questions->total() > 0)
                        {{ fa_digits($questions->total()) }} سوال
                    @else
                        بدون نتیجه
                    @endif
                </p>
                <form method="GET" action="{{ route('cafe-soal.index') }}" class="cafe-list-head__sort">
                    @if ($q !== '')
                        <input type="hidden" name="q" value="{{ $q }}">
                    @endif
                    @if ($categoryId)
                        <input type="hidden" name="category" value="{{ $categoryId }}">
                    @endif
                    <label for="cafe-sort">مرتب‌سازی</label>
                    <select name="sort" id="cafe-sort" autocomplete="off" onchange="this.form.submit()">
                        <option value="latest" @selected(($sort ?: 'latest') === 'latest')>جدیدترین</option>
                        <option value="popular" @selected($sort === 'popular')>محبوب‌ترین</option>
                        <option value="most_answers" @selected($sort === 'most_answers')>بیشترین پاسخ</option>
                    </select>
                </form>
            </div>

            @if ($questions->isEmpty())
                <div class="cafe-empty" role="status">
                    <div class="cafe-empty__icon" aria-hidden="true">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3c-4.4 0-8 3.1-8 7 0 2.3 1.2 4.4 3.1 5.7L6 20l4.1-2.1c.6.1 1.2.2 1.9.2 4.4 0 8-3.1 8-7s-3.6-7-8-7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h2 class="cafe-empty__title">هنوز سوالی در این فهرست نیست</h2>
                    <p class="cafe-empty__text">اولین نفر باشید؛ سوال حسابداری یا مالیاتی‌تان را بنویسید تا کارشناسان پاسخ دهند.</p>
                    @auth
                        <button type="button" class="cafe-ask-btn cafe-ask-btn--solid" data-cafe-ask-open>
                            <span class="cafe-ask-btn__label">سوال بپرسید</span>
                        </button>
                    @else
                        <a href="{{ $askLoginUrl }}" class="cafe-ask-btn cafe-ask-btn--solid">
                            <span class="cafe-ask-btn__label">سوال بپرسید</span>
                        </a>
                    @endauth
                </div>
            @else
                <ul class="cafe-list">
                    @foreach ($questions as $index => $question)
                        <li style="--cafe-i: {{ $index }}">
                            <a href="{{ route('cafe-soal.show', $question->slug) }}" class="cafe-item">
                                <div class="cafe-item__rail" aria-hidden="true"></div>
                                <div class="cafe-item__body">
                                    <h2 class="cafe-item__title">{{ $question->title }}</h2>
                                    <p class="cafe-item__excerpt">{{ Str::limit(strip_tags($question->body), 140) }}</p>
                                    <div class="cafe-item__meta">
                                        @if ($question->category)
                                            <span class="cafe-item__cat">{{ $question->category->name }}</span>
                                        @endif
                                        <span class="cafe-item__stat">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v10H8l-4 4V6z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                            {{ fa_digits($question->answers_count) }} پاسخ
                                        </span>
                                        <span class="cafe-item__stat">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/></svg>
                                            {{ fa_digits($question->views_count) }} بازدید
                                        </span>
                                        @if ($question->answered_at)
                                            <time datetime="{{ $question->answered_at->toDateString() }}">{{ fa_date($question->answered_at) }}</time>
                                        @endif
                                    </div>
                                </div>
                                <span class="cafe-item__arrow" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <path d="M14 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="cafe-pagination">
                    {{ $questions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@auth
<dialog class="cafe-ask-dialog" id="cafeAskDialog" aria-labelledby="cafeAskTitle">
    <form method="POST" action="{{ route('cafe-soal.store') }}" class="cafe-ask-dialog__panel">
        @csrf
        <div class="cafe-ask-dialog__head">
            <div>
                <p class="cafe-ask-dialog__eyebrow">کافه سوال</p>
                <h2 id="cafeAskTitle" class="cafe-ask-dialog__title">سوال شما</h2>
            </div>
            <button type="button" class="cafe-ask-dialog__close" data-cafe-ask-close aria-label="بستن">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <p class="cafe-ask-dialog__lead">سوال حسابداری یا مالیاتی‌تان را بنویسید؛ کارشناسان پس از بررسی پاسخ می‌دهند.</p>
        @if ($errors->any())
            <div class="cafe-form__alert" role="alert" tabindex="-1" id="cafeAskErrors">
                <p class="cafe-form__alert-title">لطفاً موارد زیر را اصلاح کنید</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="cafe-form__field">
            <label class="cafe-form__label" for="cafe-ask-category">دسته سوال</label>
            <select name="category_id" id="cafe-ask-category" class="cafe-form__input @error('category_id') is-invalid @enderror" required aria-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}">
                <option value="" disabled @selected(! old('category_id'))>انتخاب کنید...</option>
                @foreach ($cafeCategories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) old('category_id') === (string) $cat->id)>
                        {{ str_repeat('— ', $cat->treeDepth) }}{{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')<p class="cafe-form__error" role="alert">{{ $message }}</p>@enderror
            @if ($cafeCategories->isEmpty())
                <p class="cafe-form__error" role="alert">فعلاً دسته‌ای برای ثبت سوال تعریف نشده است.</p>
            @endif
        </div>
        <div class="cafe-form__field">
            <label class="cafe-form__label" for="cafe-ask-title">عنوان سوال</label>
            <input type="text" name="title" id="cafe-ask-title" value="{{ old('title') }}" class="cafe-form__input @error('title') is-invalid @enderror" required maxlength="180" placeholder="مثلاً نحوه محاسبه مالیات حقوق" aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}">
            @error('title')<p class="cafe-form__error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="cafe-form__field">
            <label class="cafe-form__label" for="cafe-ask-body">متن سوال</label>
            <textarea name="body" id="cafe-ask-body" rows="6" class="cafe-form__input @error('body') is-invalid @enderror" required placeholder="جزئیات سوال را کامل بنویسید..." aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}">{{ old('body') }}</textarea>
            @error('body')<p class="cafe-form__error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="cafe-ask-dialog__actions">
            <button type="button" class="cafe-ask-dialog__cancel" data-cafe-ask-close>انصراف</button>
            <button type="submit" class="cafe-ask-dialog__submit" @disabled($cafeCategories->isEmpty())>ثبت سوال</button>
        </div>
    </form>
</dialog>
@endauth
@endsection

@push('scripts')
@auth
<script>
(() => {
  const dialog = document.getElementById('cafeAskDialog');
  if (!dialog || typeof dialog.showModal !== 'function') return;

  const open = () => {
    if (!dialog.open) dialog.showModal();
    const alertBox = document.getElementById('cafeAskErrors');
    if (alertBox) alertBox.focus();
  };

  document.querySelectorAll('[data-cafe-ask-open]').forEach((btn) => {
    btn.addEventListener('click', open);
  });

  dialog.querySelectorAll('[data-cafe-ask-close]').forEach((btn) => {
    btn.addEventListener('click', () => dialog.close());
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });

  @if ($errors->any())
  open();
  @elseif (request()->boolean('ask'))
  open();
  @endif
})();
</script>
@endauth
@endpush
