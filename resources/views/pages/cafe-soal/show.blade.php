@extends('layouts.site')

@section('page-banner')
<div class="cafe-page cafe-page--masthead cafe-page--detail">
    <header class="cafe-masthead cafe-masthead--compact">
        <div class="cafe-masthead__inner">
            <p class="cafe-masthead__brand"><a href="{{ route('cafe-soal.index') }}">کافه سوال</a></p>
            <h1 class="cafe-masthead__title">{{ $question->title }}</h1>
            <p class="cafe-masthead__lead">
                {{ fa_digits($question->answers_count) }} پاسخ · {{ fa_digits($question->views_count) }} بازدید
                @if ($question->answered_at)
                    · {{ fa_date($question->answered_at) }}
                @endif
            </p>
        </div>
    </header>
</div>
@endsection

@section('page')
<div class="cafe-page">
    <div class="cafe-shell cafe-shell--narrow">
        @if (session('success'))
            <div class="cafe-flash cafe-flash--ok" role="status">{{ session('success') }}</div>
        @endif

        <article class="cafe-question">
            <p class="cafe-question__author">پرسش از {{ $question->user?->displayName() ?? 'کاربر' }}</p>
            <div class="cafe-question__body">{{ $question->body }}</div>
        </article>

        <section class="cafe-answers" aria-label="پاسخ‌ها">
            <h2 class="cafe-section-title">پاسخ‌ها</h2>

            @if ($official)
                <article class="cafe-answer cafe-answer--official">
                    <div class="cafe-answer__badge">پاسخ کارشناس راهبر حساب</div>
                    <div class="cafe-answer__head">
                        <strong>{{ $official->user?->displayName() ?? 'کارشناس' }}</strong>
                        <time datetime="{{ $official->created_at->toDateString() }}">{{ fa_date($official->created_at) }}</time>
                    </div>
                    <div class="cafe-answer__body">{{ $official->body }}</div>
                </article>
            @endif

            @forelse ($community as $answer)
                <article class="cafe-answer">
                    <div class="cafe-answer__head">
                        <strong>{{ $answer->user?->displayName() ?? 'کاربر' }}</strong>
                        <time datetime="{{ $answer->created_at->toDateString() }}">{{ fa_date($answer->created_at) }}</time>
                    </div>
                    <div class="cafe-answer__body">{{ $answer->body }}</div>
                </article>
            @empty
                @unless ($official)
                    <p class="cafe-empty">هنوز پاسخی ثبت نشده است.</p>
                @endunless
            @endforelse
        </section>

        <section class="cafe-reply" aria-label="ثبت پاسخ">
            <h2 class="cafe-section-title">پاسخ شما</h2>
            @auth
                <form method="POST" action="{{ route('cafe-soal.answers.store', $question->slug) }}" class="cafe-form">
                    @csrf
                    <label class="cafe-form__label" for="answer-body">متن پاسخ</label>
                    <textarea name="body" id="answer-body" rows="5" class="cafe-form__input @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
                    @error('body')<p class="cafe-form__error">{{ $message }}</p>@enderror
                    <button type="submit" class="cafe-form__submit">ارسال پاسخ</button>
                </form>
            @else
                <p class="cafe-empty">
                    برای ثبت پاسخ باید
                    <a href="{{ route('login') }}">وارد شوید</a>.
                </p>
            @endauth
        </section>
    </div>
</div>
@endsection
