@extends('layouts.admin')

@section('title', $user->exists ? 'ویرایش کاربر' : 'کاربر جدید')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.users.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">{{ $user->exists ? 'ویرایش: '.$user->name : 'کاربر جدید' }}</h4>
</div>

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card" style="max-width: 40rem;">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">نام *</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">موبایل *</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" dir="ltr" required>
        </div>
        <div class="mb-3">
            <label class="form-label">ایمیل</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">نقش *</label>
            <select name="role" class="form-select">
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">دسترسی‌های اضافی</label>
            @foreach ($permissions as $perm => $label)
                <div class="form-check">
                    <input type="checkbox" name="permissions[]" value="{{ $perm }}" class="form-check-input" id="perm_{{ $perm }}"
                        @checked(in_array($perm, old('permissions', $user->permissions ?? [])))>
                    <label class="form-check-label" for="perm_{{ $perm }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
        <button type="submit" class="btn btn-primary">ذخیره</button>
    </div>
</form>
@endsection
