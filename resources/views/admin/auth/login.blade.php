@extends('layouts.admin-auth')

@section('title', 'ورود')

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="{{ route('home') }}" class="app-brand-link justify-content-center">
              <img src="{{ asset('images/rahbarhesab/logo-full.png') }}" alt="راهبر حساب" class="h-12 w-auto object-contain">
            </a>
          </div>

          <h4 class="mb-1 pt-2">ورود به پنل مدیریت 👋</h4>
          <p class="mb-4">برای مدیریت محتوای سایت وارد شوید.</p>

          @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
          @endif

          <form method="POST" action="{{ route('admin.login') }}" class="mb-3" autocomplete="on">
            @csrf
            <div class="mb-3">
              <label class="form-label" for="email">ایمیل</label>
              <input autofocus class="form-control" id="email" name="email" value="{{ old('email') }}" type="email" dir="ltr" autocomplete="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="password">رمز عبور</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password" autocomplete="current-password" required>
                <span class="input-group-text cursor-pointer" data-password-toggle role="button" tabindex="0" aria-label="نمایش رمز عبور">
                  <svg data-eye-off xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"/><path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c5 0 9.27 3.11 11 7.5a11.8 11.8 0 0 1-2.16 3.19"/><path d="M6.61 6.61A11.8 11.8 0 0 0 1 12.5C2.73 16.89 7 20 12 20a10.9 10.9 0 0 0 5.39-1.61"/></svg>
                  <svg data-eye class="d-none" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
              </div>
            </div>
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember-me">
                <label class="form-check-label" for="remember-me">مرا به خاطر بسپار</label>
              </div>
            </div>
            <button class="btn btn-primary d-grid w-100" type="submit">ورود به سیستم</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
