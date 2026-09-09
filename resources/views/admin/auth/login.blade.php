@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$configData['hasCustomizer'] = false;
@endphp
@extends('layouts.vuexy.commonMaster')

@section('title', 'ورود')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js'
])
@endsection

@section('layoutContent')
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
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password">رمز عبور</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password" autocomplete="current-password" required>
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
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

@section('page-script')
@vite(['resources/assets/js/pages-auth.js'])
@endsection
