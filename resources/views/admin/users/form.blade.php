@extends('layouts.admin')

@section('title', $user->exists ? 'ویرایش کاربر' : 'کاربر جدید')

@section('heading', $user->exists ? 'ویرایش: '.$user->displayName() : 'کاربر جدید')

@section('content')
@php
    $status = old('status', $user->status ?: 'active');
    $statusLabels = \App\Models\User::statusLabels();
@endphp

@if ($user->exists)
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted small">عضویت</div>
                    <div>{{ fa_date($user->created_at) }}</div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted small">آخرین ورود</div>
                    <div>{{ $user->last_login_at ? fa_date($user->last_login_at) : '—' }}</div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted small">سفارش‌ها</div>
                    <div>{{ fa_digits($ordersCount) }}</div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="text-muted small">دوره‌ها</div>
                    <div>{{ fa_digits($enrollmentsCount) }}</div>
                </div>
            </div>
        </div>
    </div>
@endif

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="user-first-name">نام *</label>
                <input id="user-first-name" type="text" name="first_name" value="{{ old('first_name', $user->first_name ?: ($user->exists ? $user->name : '')) }}" class="form-control @error('first_name') is-invalid @enderror" required>
                @error('first_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-last-name">نام خانوادگی</label>
                <input id="user-last-name" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="form-control @error('last_name') is-invalid @enderror">
                @error('last_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-phone">موبایل *</label>
                <input id="user-phone" type="text" name="phone" value="{{ old('phone', $user->mobile ?: $user->phone) }}" class="form-control @error('phone') is-invalid @enderror" dir="ltr" required>
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-email">ایمیل</label>
                <input id="user-email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" dir="ltr">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-role">نقش *</label>
                <select id="user-role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role', $currentRole) === $role->name)>{{ $role->displayName() }}</option>
                    @endforeach
                </select>
                <div class="form-text">دسترسی کاربر از روی نقش مشخص می‌شود.@if (admin_can('users', 'create')) <a href="{{ route('admin.roles.create') }}">نقش جدید</a>@endif</div>
                @error('role')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-status">وضعیت *</label>
                <select id="user-status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-password">رمز عبور</label>
                <input id="user-password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                <div class="form-text">{{ $user->exists ? 'برای نگه داشتن رمز فعلی، این فیلد را خالی بگذارید.' : 'اگر خالی بماند، کاربر فقط با کد یکبارمصرف وارد می‌شود.' }}</div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-password-confirmation">تکرار رمز عبور</label>
                <input id="user-password-confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
            </div>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary">ذخیره</button>
        </div>
    </div>
</form>
@endsection
