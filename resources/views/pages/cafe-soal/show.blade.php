@extends('layouts.site')

@section('page-banner')
<div class="cafe-hero cafe-hero--compact" aria-labelledby="cafeDetailTitle">
    <div class="cafe-hero__atmosphere" aria-hidden="true">
        <span class="cafe-hero__glow"></span>
        <span class="cafe-hero__steam cafe-hero__steam--a"></span>
        <span class="cafe-hero__steam cafe-hero__steam--b"></span>
    </div>
    <div class="cafe-hero__inner cafe-hero__inner--start">
        <p class="cafe-hero__brand">
            <a href="{{ route('cafe-soal.index') }}">کافه سوال راهبر حساب</a>
        </p>
        <h1 id="cafeDetailTitle" class="cafe-hero__title cafe-hero__title--sm">{{ wp_texturize($question->title) }}</h1>
        <p class="cafe-hero__lead cafe-hero__lead--meta">
            @if ($question->category)
                <span>{{ $question->category->name }}</span>
            @endif
            <span>{{ fa_digits($question->answers_count) }} پاسخ</span>
            <span>{{ fa_digits($question->views_count) }} بازدید</span>
            @if ($question->answered_at)
                <time datetime="{{ $question->answered_at->toDateString() }}">{{ fa_date($question->answered_at) }}</time>
            @endif
        </p>
    </div>
</div>
@endsection

@section('page')
<div class="cafe-page">
    <div class="cafe-shell cafe-shell--narrow">
        @if (session('success'))
            <div class="cafe-flash cafe-flash--ok" role="status">{{ session('success') }}</div>
        @endif

        @php
            $askerName = $question->user?->displayName() ?? 'کاربر';
            if ($askerName === 'مدیر سایت') {
                $askerName = 'راهبرحساب';
            }
        @endphp
        <article class="cafe-question">
            <header class="cafe-question__head">
                <span class="cafe-question__avatar" aria-hidden="true">
                    {{ mb_substr($askerName, 0, 1) }}
                </span>
                <div>
                    <p class="cafe-question__author">پرسش از {{ $askerName }}</p>
                    @if ($question->answered_at)
                        <time class="cafe-question__date" datetime="{{ $question->answered_at->toDateString() }}">{{ fa_date($question->answered_at) }}</time>
                    @endif
                </div>
            </header>
            <div class="cafe-question__body cafe-answer__body--rich">{!! $question->bodyHtml() !!}</div>
            @if ($question->video_url)
                <video class="cafe-question__video" controls preload="none" playsinline @if($question->video_poster) poster="{{ $question->video_poster }}" @endif>
                    <source src="{{ $question->video_url }}" type="video/mp4">
                </video>
            @endif
        </article>

        <section class="cafe-answers" aria-label="پاسخ‌ها">
            <h2 class="cafe-section-title">پاسخ‌ها</h2>

            @if ($official)
                <article class="cafe-answer cafe-answer--official">
                    <div class="cafe-answer__seal" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3l2.2 4.5 5 .7-3.6 3.5.9 5L12 14.8 7.5 16.7l.9-5L4.8 8.2l5-.7L12 3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="cafe-answer__badge">پاسخ کارشناس راهبر حساب</div>
                    <div class="cafe-answer__head">
                        <strong>{{ $official->user?->displayName() ?? 'کارشناس' }}</strong>
                        <time datetime="{{ $official->created_at->toDateString() }}">{{ fa_date($official->created_at) }}</time>
                    </div>
                    <div class="cafe-answer__body cafe-answer__body--rich">{!! $official->bodyHtml() !!}</div>
                </article>
            @endif

            @forelse ($community as $answer)
                <article class="cafe-answer">
                    <div class="cafe-answer__head">
                        <div class="cafe-answer__who">
                            <span class="cafe-answer__avatar" aria-hidden="true">{{ mb_substr($answer->authorDisplayName(), 0, 1) }}</span>
                            <strong>{{ $answer->authorDisplayName() }}</strong>
                        </div>
                        <time datetime="{{ $answer->created_at->toDateString() }}">{{ fa_date($answer->created_at) }}</time>
                    </div>
                    <div class="cafe-answer__body cafe-answer__body--rich">{!! $answer->bodyHtml() !!}</div>
                </article>
                @foreach ($answer->replies as $reply)
                    <article class="cafe-answer cafe-answer--reply">
                        <div class="cafe-answer__head">
                            <div class="cafe-answer__who">
                                <span class="cafe-answer__avatar" aria-hidden="true">{{ mb_substr($reply->authorDisplayName(), 0, 1) }}</span>
                                <strong>{{ $reply->authorDisplayName() }}</strong>
                            </div>
                            <time datetime="{{ $reply->created_at->toDateString() }}">{{ fa_date($reply->created_at) }}</time>
                        </div>
                        <div class="cafe-answer__body cafe-answer__body--rich">{!! $reply->bodyHtml() !!}</div>
                    </article>
                @endforeach
            @empty
                @unless ($official)
                    <div class="cafe-empty cafe-empty--inline" role="status">
                        <p class="cafe-empty__text">هنوز پاسخی ثبت نشده است. اگر پاسخی دارید، پایین بنویسید.</p>
                    </div>
                @endunless
            @endforelse
        </section>

        <section class="cafe-reply" aria-label="ثبت پاسخ">
            <h2 class="cafe-section-title">پاسخ شما</h2>
            @auth
                <form method="POST" action="{{ route('cafe-soal.answers.store', $question->slug) }}" class="cafe-form cafe-form--card">
                    @csrf
                    @if ($errors->any())
                        <div class="cafe-form__alert" role="alert" tabindex="-1">
                            <p class="cafe-form__alert-title">لطفاً متن پاسخ را بررسی کنید</p>
                        </div>
                    @endif
                    <label class="cafe-form__label" for="answer-body">متن پاسخ</label>
                    <textarea name="body" id="answer-body" rows="5" class="cafe-form__input @error('body') is-invalid @enderror" required aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}">{{ old('body') }}</textarea>
                    @error('body')<p class="cafe-form__error" role="alert">{{ $message }}</p>@enderror
                    <button type="submit" class="cafe-form__submit">ارسال پاسخ</button>
                </form>
            @else
                <div class="cafe-empty cafe-empty--inline">
                    <p class="cafe-empty__text">
                        برای ثبت پاسخ باید
                        <a href="{{ route('login', ['redirect' => url()->current()]) }}">وارد شوید</a>.
                    </p>
                </div>
            @endauth
        </section>
    </div>
</div>
@endsection
