@extends('layouts.admin')

@section('title', 'برچسب‌ها')

@section('content')
<div class="mb-4">
    <h4 class="mb-1">برچسب‌ها</h4>
    <p class="text-muted mb-0">برچسب‌ها درخت نیستند. یک برچسب می‌تواند به چند مطلب وصل شود و دستهٔ اصلی مطلب را عوض نمی‌کند.</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4" style="max-width: 42rem;">
    <div class="card-header"><h5 class="mb-0">برچسب جدید</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.tags.store') }}" class="row g-3">
            @csrf
            <div class="col-sm-6">
                <label class="form-label">نام</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" class="form-control" dir="ltr" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">افزودن</button>
            </div>
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
                    <th>مطالب</th>
                    <th style="width: 10rem;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tags as $tag)
                    <tr>
                        <td>
                            <form id="tag-update-{{ $tag->id }}" method="POST" action="{{ route('admin.tags.update', $tag) }}">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $tag->name }}" class="form-control form-control-sm" required>
                            </form>
                        </td>
                        <td>
                            <input form="tag-update-{{ $tag->id }}" type="text" name="slug" value="{{ $tag->slug }}" class="form-control form-control-sm" dir="ltr" required>
                        </td>
                        <td class="text-muted small">{{ $tag->posts_count }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <button form="tag-update-{{ $tag->id }}" type="submit" class="btn btn-sm btn-label-primary">ذخیره</button>
                                <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm('حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">برچسبی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
