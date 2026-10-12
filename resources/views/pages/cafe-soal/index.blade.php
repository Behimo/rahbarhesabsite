@extends('layouts.site')

@php
    $askLoginUrl = route('login', ['redirect' => route('cafe-soal.index', ['ask' => 1])]);
@endphp

@section('page-banner')
<div class="cafe-page cafe-page--masthead">
    <header class="cafe-masthead">
        <div class="cafe-masthead__inner">
            <p class="cafe-masthead__brand">راهبر حساب</p>
            <h1 class="cafe-masthead__title">کافه سوال</h1>
            <p class="cafe-masthead__lead">پرسش و پاسخ تخصصی حسابداری، مالیات و امور مالی</p>
            @auth
                <button type="button" class="cafe-ask-btn" data-cafe-ask-open>
                    <span class="cafe-ask-btn__glow" aria-hidden="true"></span>
                    <span class="cafe-ask-btn__icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3c-4.4 0-8 3.1-8 7 0 2.3 1.2 4.4 3.1 5.7L6 20l4.1-2.1c.6.1 1.2.2 1.9.2 4.4 0 8-3.1 8-7s-3.6-7-8-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M9.5 10.5h5M12 8v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="cafe-ask-btn__label">سوال شما</span>
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
                    <span class="cafe-ask-btn__label">سوال شما</span>
                </a>
            @endauth
        </div>
    </header>
</div>
@endsection

@section('page')
<div class="cafe-page">
    <div class="cafe-shell">
        @if (session('success'))
            <div class="cafe-flash cafe-flash--ok" role="status">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('cafe-soal.index') }}" class="cafe-toolbar" role="search">
            <div class="cafe-toolbar__search">
                <label class="cafe-sr-only" for="cafe-q">جستجوی سوال</label>
                <input type="search" name="q" id="cafe-q" value="{{ $q }}" placeholder="جستجو بین سوالات..." class="cafe-toolbar__input">
                <button type="submit" class="cafe-toolbar__submit">جستجو</button>
            </div>
            <div class="cafe-toolbar__sorts" role="group" aria-label="مرتب‌سازی">
                @foreach ([
                    'latest' => 'جدیدترین',
                    'popular' => 'محبوب‌ترین',
                    'most_answers' => 'بیشترین پاسخ',
                ] as $value => $label)
                    <a href="{{ route('cafe-soal.index', array_filter(['q' => $q ?: null, 'sort' => $value])) }}"
                       class="cafe-sort{{ $sort === $value ? ' is-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </form>

        @if ($questions->isEmpty())
            <div class="cafe-empty" role="status">
                <p>سوالی یافت نشد.</p>
                @auth
                    <button type="button" class="cafe-ask-btn cafe-ask-btn--solid" data-cafe-ask-open>
                        <span class="cafe-ask-btn__label">سوال شما</span>
                    </button>
                @else
                    <a href="{{ $askLoginUrl }}" class="cafe-ask-btn cafe-ask-btn--solid">
                        <span class="cafe-ask-btn__label">سوال شما</span>
                    </a>
                @endauth
            </div>
        @else
            <ul class="cafe-list">
                @foreach ($questions as $question)
                    <li>
                        <a href="{{ route('cafe-soal.show', $question->slug) }}" class="cafe-item">
                            <div class="cafe-item__body">
                                <h2 class="cafe-item__title">{{ $question->title }}</h2>
                                <p class="cafe-item__excerpt">{{ Str::limit(strip_tags($question->body), 140) }}</p>
                            </div>
                            <div class="cafe-item__meta">
                                <span>{{ fa_digits($question->answers_count) }} پاسخ</span>
                                <span>{{ fa_digits($question->views_count) }} بازدید</span>
                                @if ($question->answered_at)
                                    <time datetime="{{ $question->answered_at->toDateString() }}">{{ fa_date($question->answered_at) }}</time>
                                @endif
                            </div>
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
        <div class="cafe-form__field">
            <label class="cafe-form__label" for="cafe-ask-title">عنوان سوال</label>
            <input type="text" name="title" id="cafe-ask-title" value="{{ old('title') }}" class="cafe-form__input @error('title') is-invalid @enderror" required maxlength="180" placeholder="مثلاً نحوه محاسبه مالیات حقوق">
            @error('title')<p class="cafe-form__error">{{ $message }}</p>@enderror
        </div>
        <div class="cafe-form__field">
            <label class="cafe-form__label" for="cafe-ask-body">متن سوال</label>
            <textarea name="body" id="cafe-ask-body" rows="6" class="cafe-form__input @error('body') is-invalid @enderror" required placeholder="جزئیات سوال را کامل بنویسید...">{{ old('body') }}</textarea>
            @error('body')<p class="cafe-form__error">{{ $message }}</p>@enderror
        </div>
        <div class="cafe-ask-dialog__actions">
            <button type="button" class="cafe-ask-dialog__cancel" data-cafe-ask-close>انصراف</button>
            <button type="submit" class="cafe-ask-dialog__submit">ثبت سوال</button>
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

  const params = new URLSearchParams(window.location.search);
  @if ($errors->any())
  open();
  @elseif (request()->boolean('ask'))
  open();
  @endif
})();
</script>
@endauth
@endpush
