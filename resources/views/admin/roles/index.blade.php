@extends('layouts.admin')

@section('title', 'نقش‌ها')

@section('lede', 'تعریف نقش و تعیین دسترسی هر نقش. این نقش‌ها هنگام ساخت و ویرایش کاربر انتخاب می‌شوند.')

@section('actions')
    @if (admin_can('users', 'create'))
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>نقش جدید
        </a>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>شناسه</th>
                    <th>دسترسی</th>
                    <th>کاربران</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td>
                            {{ $role->displayName() }}
                            @if ($role->is_system)
                                <span class="badge bg-label-secondary ms-1">سیستمی</span>
                            @endif
                        </td>
                        <td dir="ltr">{{ $role->name }}</td>
                        <td>{{ $role->permissions_count }}</td>
                        <td>{{ $role->users_count }}</td>
                        <td>
                            @if (admin_can('users', 'update'))
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                            @endif
                            @if (admin_can('users', 'delete') && ! $role->is_system && $role->users_count === 0)
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline" onsubmit="return confirm('این نقش حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">نقشی ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
