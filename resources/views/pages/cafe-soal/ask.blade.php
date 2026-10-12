@extends('layouts.site')

@section('page-banner')
<div class="cafe-page cafe-page--masthead cafe-page--detail">
    <header class="cafe-masthead cafe-masthead--compact">
        <div class="cafe-masthead__inner">
            <p class="cafe-masthead__brand"><a href="{{ route('cafe-soal.index') }}">کافه سوال</a></p>
            <h1 class="cafe-masthead__title">پرسیدن سوال</h1>
            <p class="cafe-masthead__lead">سوال خود را بنویسید؛ کارشناسان پس از بررسی پاسخ می‌دهند.</p>
        </div>
    </header>
</div>
@endsection

@section('page')
<div class="cafe-page">
    <div class="cafe-shell cafe-shell--narrow">
        <form method="POST" action="{{ route('cafe-soal.store') }}" class="cafe-form cafe-form--card">
            @csrf
            <div class="cafe-form__field">
                <label class="cafe-form__label" for="title">عنوان سوال</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" class="cafe-form__input @error('title') is-invalid @enderror" required maxlength="180">
                @error('title')<p class="cafe-form__error">{{ $message }}</p>@enderror
            </div>
            <div class="cafe-form__field">
                <label class="cafe-form__label" for="body">متن سوال</label>
                <textarea name="body" id="body" rows="8" class="cafe-form__input @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
                @error('body')<p class="cafe-form__error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="cafe-form__submit">ثبت سوال</button>
        </form>
    </div>
</div>
@endsection
