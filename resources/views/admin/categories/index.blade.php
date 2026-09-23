@extends('layouts.admin')

@section('title', 'دسته‌بندی‌ها')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.posts.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت به بلاگ</a>
    <h4 class="mt-2 mb-0">دسته‌بندی‌های بلاگ</h4>
</div>

<div class="card mb-4" style="max-width: 42rem;">
    <div class="card-header"><h5 class="mb-0">دسته جدید</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">نام</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" dir="ltr" required>
                </div>
                <div class="col-12">
                    <label class="form-label">توضیح</label>
                    <textarea name="description" rows="2" class="form-control" maxlength="500"></textarea>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">ترتیب</label>
                    <input type="number" name="sort_order" class="form-control" min="0" value="0">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">افزودن</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>Slug</th>
                    <th>توضیح</th>
                    <th>ترتیب</th>
                    <th>مقالات</th>
                    <th style="width: 10rem;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <form id="cat-update-{{ $category->id }}" method="POST" action="{{ route('admin.categories.update', $category) }}">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $category->name }}" class="form-control form-control-sm" required>
                            </form>
                        </td>
                        <td>
                            <input form="cat-update-{{ $category->id }}" type="text" name="slug" value="{{ $category->slug }}" class="form-control form-control-sm" dir="ltr" required>
                        </td>
                        <td>
                            <input form="cat-update-{{ $category->id }}" type="text" name="description" value="{{ $category->description }}" class="form-control form-control-sm" maxlength="500">
                        </td>
                        <td style="max-width: 5rem;">
                            <input form="cat-update-{{ $category->id }}" type="number" name="sort_order" value="{{ $category->sort_order }}" class="form-control form-control-sm" min="0">
                        </td>
                        <td class="text-muted small">{{ $category->posts_count }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <button form="cat-update-{{ $category->id }}" type="submit" class="btn btn-sm btn-label-primary">ذخیره</button>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">دسته‌ای ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
