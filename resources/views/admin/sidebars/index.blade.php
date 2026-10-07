@extends('layouts.admin')

@section('title', 'سایدبارها')

@section('lede', 'برای هر صفحه مشخص کنید کنار محتوا چه فهرستی دیده شود. اگر چند سایدبار روی یک صفحه باشد، به ترتیب زیر هم می‌آیند.')

@section('actions')
    @if (admin_can('sidebars', 'create'))
        <a href="{{ route('admin.sidebars.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>سایدبار جدید
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
                    <th>محتوا</th>
                    <th>نمایش</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sidebars as $sidebar)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $sidebar->name }}</div>
                            <div class="text-muted small">{{ $sidebar->title }}</div>
                        </td>
                        <td>{{ $sidebar->contentSummary() }}</td>
                        <td>{{ $sidebar->placementSummary() }}</td>
                        <td>
                            <span class="badge {{ $sidebar->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $sidebar->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if (admin_can('sidebars', 'update'))
                                <a href="{{ route('admin.sidebars.edit', $sidebar) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                            @endif
                            @if (admin_can('sidebars', 'delete'))
                                <form method="POST" action="{{ route('admin.sidebars.destroy', $sidebar) }}" class="d-inline" onsubmit="return confirm('این سایدبار حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-label-danger">حذف</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">سایدباری ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($sidebars->hasPages())
        <div class="card-footer">{{ $sidebars->links() }}</div>
    @endif
</div>
@endsection
