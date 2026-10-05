@extends('layouts.admin')

@section('title', 'کاربران')

@section('lede', 'مدیریت کاربران و دسترسی‌ها')

@section('actions')
    @if (admin_can('users', 'view'))
        <a href="{{ route('admin.roles.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-shield me-1"></i>نقش‌ها
        </a>
    @endif
    @if (admin_can('users', 'create'))
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>کاربر جدید
        </a>
    @endif
@endsection

@section('content')

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>موبایل</th>
                    <th>نقش</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->displayName() }}</td>
                        <td dir="ltr">{{ $user->mobile ?: $user->phone }}</td>
                        <td><span class="badge bg-label-info">{{ $user->roles->first()?->displayName() ?? '—' }}</span></td>
                        <td>
                            @php
                                $statusClass = match ($user->status) {
                                    'banned' => 'bg-label-danger',
                                    'suspended' => 'bg-label-warning',
                                    default => 'bg-label-success',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ \App\Models\User::statusLabels()[$user->status] ?? 'فعال' }}</span>
                        </td>
                        <td>
                            @if (admin_can('users', 'update'))
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                            @endif
                            @if (admin_can('users', 'delete'))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">کاربری ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
        <div class="card-footer">{{ $users->links() }}</div>
    @endif
</div>
@endsection
