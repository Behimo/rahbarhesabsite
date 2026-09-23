@extends('layouts.admin')

@section('title', 'بلاگ')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="mb-1">مقالات بلاگ</h4>
        <p class="text-muted mb-0">مدیریت مقالات، پیش‌نویس‌ها و سطل زباله</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-label-secondary">دسته‌بندی‌ها</a>
        <a href="{{ route('admin.posts.index', ['trashed' => 1]) }}" class="btn btn-label-warning">
            سطل زباله @if ($trashCount > 0)<span class="badge bg-danger ms-1">{{ $trashCount }}</span>@endif
        </a>
        <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>مقاله جدید
        </a>
    </div>
</div>

<form method="GET" class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">جستجو</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="عنوان، slug یا خلاصه">
            </div>
            <div class="col-md-3">
                <label class="form-label">دسته</label>
                <select name="category" class="form-select">
                    <option value="">همه</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['category'] === (string) $category->id)>{{ $category->name }}</option>
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
                <a href="{{ route('admin.posts.index') }}" class="btn btn-label-secondary">پاک</a>
            </div>
        </div>
    </div>
</form>

@if ($filters['trashed'])
    <div class="alert alert-warning">در حال مشاهده سطل زباله هستید. <a href="{{ route('admin.posts.index') }}">بازگشت به لیست اصلی</a></div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>دسته</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                    <th>بازدید</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $post->title }}</div>
                            <small class="text-muted" dir="ltr">/blog/{{ $post->slug }}</small>
                        </td>
                        <td>{{ $post->category?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $post->published_at?->format('Y/m/d H:i') ?? '—' }}</td>
                        <td>
                            @if ($post->trashed())
                                <span class="badge bg-label-danger">حذف‌شده</span>
                            @elseif ($post->status === 'scheduled' || ($post->is_published && $post->published_at?->isFuture()))
                                <span class="badge bg-label-info">زمان‌بندی</span>
                            @elseif ($post->isLive())
                                <span class="badge bg-label-success">منتشر</span>
                            @else
                                <span class="badge bg-label-warning">پیش‌نویس</span>
                            @endif
                        </td>
                        <td>{{ number_format($post->views) }}</td>
                        <td class="text-nowrap">
                            @if ($post->trashed())
                                <form method="POST" action="{{ route('admin.posts.restore', $post->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-label-success">بازیابی</button>
                                </form>
                                <form method="POST" action="{{ route('admin.posts.force-destroy', $post->id) }}" class="d-inline" onsubmit="return confirm('حذف دائمی؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-label-danger">حذف دائم</button>
                                </form>
                            @else
                                <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                                <a href="{{ route('admin.posts.preview', $post) }}" class="btn btn-sm btn-label-secondary" target="_blank">پیش‌نمایش</a>
                                <form method="POST" action="{{ route('admin.posts.duplicate', $post) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-label-info">کپی</button>
                                </form>
                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="d-inline" onsubmit="return confirm('به سطل زباله منتقل شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">مقاله‌ای یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $posts->links() }}</div>
@endsection
