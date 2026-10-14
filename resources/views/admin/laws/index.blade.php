@extends('layouts.admin')

@section('title', request()->boolean('trashed') ? 'سطل زباله مواد قانونی' : 'مواد قانونی')

@section('lede', 'مدیریت مواد قانونی حسابداری و سیر تحول آن‌ها')

@section('actions')
    @if (admin_can('taxonomies', 'view'))
        <a href="{{ route('admin.categories.index', ['type' => 'law']) }}" class="btn btn-label-secondary">دسته‌بندی‌ها</a>
    @endif
    <a href="{{ route('admin.laws.index', ['trashed' => 1]) }}" class="btn btn-label-warning">
        سطل زباله @if ($trashCount > 0)<span class="badge bg-danger ms-1">{{ $trashCount }}</span>@endif
    </a>
    @if (admin_can('laws', 'create'))
        <a href="{{ route('admin.laws.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>ماده جدید
        </a>
    @endif
@endsection

@section('content')
@include('admin.partials.category-ui')

<form method="GET" class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">جستجو</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="شماره ماده، عنوان یا slug">
            </div>
            <div class="col-md-3">
                <label class="form-label">دسته</label>
                <select name="category" class="form-select">
                    <option value="">همه</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['category'] === (string) $category->id)>{{ str_repeat('— ', $category->treeDepth) }}{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="">همه</option>
                    <option value="published" @selected($filters['status'] === 'published')>منتشر</option>
                    <option value="draft" @selected($filters['status'] === 'draft')>پیش‌نویس</option>
                    <option value="scheduled" @selected($filters['status'] === 'scheduled')>زمان‌بندی</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                @if ($filters['trashed'])
                    <input type="hidden" name="trashed" value="1">
                @endif
                <button class="btn btn-primary flex-grow-1">اعمال</button>
                <a href="{{ route('admin.laws.index') }}" class="btn btn-label-secondary">پاک</a>
            </div>
        </div>
    </div>
</form>

@if ($filters['trashed'])
    <div class="alert alert-warning">در حال مشاهده سطل زباله هستید. <a href="{{ route('admin.laws.index') }}">بازگشت به لیست اصلی</a></div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ماده</th>
                    <th>دسته</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                    <th>بازدید</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($laws as $law)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $law->display_title }}</div>
                            <small class="text-muted" dir="ltr">/laws/{{ $law->slug }}</small>
                        </td>
                        <td>
                            @if ($law->category)
                                <div>{{ $law->category->name }}</div>
                                @if ($law->category->parent_id)
                                    <div class="cat-path">{{ $law->category->full_slug }}</div>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $law->published_at?->format('Y/m/d H:i') ?? '—' }}</td>
                        <td>
                            @if ($law->trashed())
                                <span class="badge bg-label-danger">حذف‌شده</span>
                            @elseif ($law->status === 'scheduled' || ($law->is_published && $law->published_at?->isFuture()))
                                <span class="badge bg-label-info">زمان‌بندی</span>
                            @elseif ($law->isLive())
                                <span class="badge bg-label-success">منتشر</span>
                            @else
                                <span class="badge bg-label-warning">پیش‌نویس</span>
                            @endif
                        </td>
                        <td>{{ number_format($law->views) }}</td>
                        <td class="text-nowrap">
                            @if ($law->trashed())
                                @if (admin_can('laws', 'update'))
                                    <form method="POST" action="{{ route('admin.laws.restore', $law->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-label-success">بازیابی</button>
                                    </form>
                                @endif
                                @if (admin_can('laws', 'delete'))
                                    <form method="POST" action="{{ route('admin.laws.force-destroy', $law->id) }}" class="d-inline" onsubmit="return confirm('حذف دائمی؟')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-label-danger">حذف دائم</button>
                                    </form>
                                @endif
                            @else
                                @if (admin_can('laws', 'update'))
                                    <a href="{{ route('admin.laws.edit', $law) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                                @endif
                                <a href="{{ route('admin.laws.preview', $law) }}" class="btn btn-sm btn-label-secondary" target="_blank">پیش‌نمایش</a>
                                @if (admin_can('laws', 'delete'))
                                    <form method="POST" action="{{ route('admin.laws.destroy', $law) }}" class="d-inline" onsubmit="return confirm('به سطل زباله منتقل شود؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">ماده قانونی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $laws->links() }}</div>
@endsection
