@extends('layouts.admin')

@section('title', $user->exists ? 'ویرایش کاربر' : 'کاربر جدید')

@section('heading', $user->exists ? 'ویرایش: '.$user->name : 'کاربر جدید')

@section('content')
<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="user-name">نام *</label>
                <input id="user-name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-phone">موبایل *</label>
                <input id="user-phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" dir="ltr" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-email">ایمیل</label>
                <input id="user-email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" dir="ltr">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="user-role">نقش *</label>
                <select id="user-role" name="role" class="form-select">
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $currentRole) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset class="col-12">
                <legend class="form-label float-none w-auto px-0">دسترسی‌های مستقیم، علاوه بر نقش</legend>
                <div class="row g-2">
                    @foreach ($permissions as $perm => $label)
                        <div class="col-md-6 col-xl-4">
                            <div class="form-check">
                                <input type="checkbox" name="permissions[]" value="{{ $perm }}" class="form-check-input" id="perm_{{ $perm }}"
                                    @checked(in_array($perm, old('permissions', $directPermissions), true))>
                                <label class="form-check-label" for="perm_{{ $perm }}">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </fieldset>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary">ذخیره</button>
        </div>
    </div>
</form>
@endsection
