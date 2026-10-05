@extends('layouts.admin')

@section('title', 'پاپ‌آپ‌ها')

@section('actions')
    @if (admin_can('popups', 'create'))
        <a href="{{ route('admin.popups.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>پاپ‌آپ جدید
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
                    <th>نمایش</th>
                    <th>مخاطب</th>
                    <th>تکرار</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($popups as $popup)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $popup->name }}</div>
                            <div class="text-muted small">{{ $popup->title }}</div>
                        </td>
                        <td>{{ $popup->placementSummary() }}</td>
                        <td>{{ \App\Models\CmsPopup::AUDIENCES[$popup->audience] ?? $popup->audience }}</td>
                        <td>{{ \App\Models\CmsPopup::FREQUENCIES[$popup->frequency] ?? $popup->frequency }}</td>
                        <td>
                            <span class="badge {{ $popup->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $popup->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            @if (admin_can('popups', 'update'))
                                <a href="{{ route('admin.popups.edit', $popup) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                            @endif
                            @if (admin_can('popups', 'delete'))
                                <form method="POST" action="{{ route('admin.popups.destroy', $popup) }}" class="d-inline" onsubmit="return confirm('این پاپ‌آپ حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-label-danger">حذف</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">پاپ‌آپی ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($popups->hasPages())
        <div class="card-footer">{{ $popups->links() }}</div>
    @endif
</div>
@endsection
