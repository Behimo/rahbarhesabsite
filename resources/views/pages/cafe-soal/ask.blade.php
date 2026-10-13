@extends('layouts.site')

@section('page-banner')
<div class="cafe-hero cafe-hero--compact" aria-labelledby="cafeAskPageTitle">
    <div class="cafe-hero__atmosphere" aria-hidden="true">
        <span class="cafe-hero__glow"></span>
        <span class="cafe-hero__steam cafe-hero__steam--a"></span>
    </div>
    <div class="cafe-hero__inner cafe-hero__inner--start">
        <p class="cafe-hero__brand">
            <a href="{{ route('cafe-soal.index') }}">کافه سوال</a>
        </p>
        <h1 id="cafeAskPageTitle" class="cafe-hero__title cafe-hero__title--sm">پرسیدن سوال</h1>
        <p class="cafe-hero__lead">سوال خود را بنویسید؛ کارشناسان پس از بررسی پاسخ می‌دهند.</p>
    </div>
</div>
@endsection

@section('page')
<div class="cafe-page">
    <div class="cafe-shell cafe-shell--narrow">
        <form method="POST" action="{{ route('cafe-soal.store') }}" class="cafe-form cafe-form--card">
            @csrf
            @if ($errors->any())
                <div class="cafe-form__alert" role="alert" tabindex="-1">
                    <p class="cafe-form__alert-title">لطفاً موارد زیر را اصلاح کنید</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="cafe-form__field">
                <label class="cafe-form__label" for="title">عنوان سوال</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" class="cafe-form__input @error('title') is-invalid @enderror" required maxlength="180" aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}">
                @error('title')<p class="cafe-form__error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="cafe-form__field">
                <label class="cafe-form__label" for="body">متن سوال</label>
                <textarea name="body" id="body" rows="8" class="cafe-form__input @error('body') is-invalid @enderror" required aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}">{{ old('body') }}</textarea>
                @error('body')<p class="cafe-form__error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="cafe-form__submit">ثبت سوال</button>
        </form>
    </div>
</div>
@endsection
