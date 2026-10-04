@php
$configData = Helper::appClasses();
@endphp
<!DOCTYPE html>
<html lang="{{ session()->get('locale') ?? app()->getLocale() }}" class="{{ $configData['style'] }}-style customizer-hide" dir="{{ $configData['textDirection'] }}" data-theme="{{ $configData['theme'] }}" data-base-url="{{ url('/') }}" data-framework="laravel">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>@yield('title') — {{ config('variables.templateName', 'بیسان') }} {{ config('variables.templateSuffix', 'پنل مدیریت') }}</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" href="{{ asset('images/bisan/logo-icon.png') }}" />
  @vite(['resources/css/admin-auth.scss'])
</head>
<body>
  @yield('content')
  @vite(['resources/js/admin-auth.js'])
</body>
</html>
