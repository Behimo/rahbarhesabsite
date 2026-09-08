@extends('layouts.admin')

@section('title', $product->exists ? 'ویرایش دوره' : 'دوره جدید')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.courses.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">{{ $product->exists ? 'ویرایش: '.$product->title : 'دوره جدید' }}</h4>
</div>

<form method="POST" action="{{ $product->exists ? route('admin.courses.update', $product) : route('admin.courses.store') }}" class="card mb-4">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="card-body">
        <h5 class="mb-3">اطلاعات دوره</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">عنوان *</label>
                <input type="text" name="title" value="{{ old('title', $product->title) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Slug *</label>
                <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control" dir="ltr" required>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">زیرعنوان</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $product->subtitle) }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">قیمت (تومان)</label>
                <input type="number" name="price" value="{{ old('price', $product->price ?? 0) }}" class="form-control" min="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">قیمت تخفیف</label>
                <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" class="form-control" min="0">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">توضیحات</label>
            <textarea name="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">سطح</label>
                <select name="level" class="form-select">
                    @foreach (['beginner' => 'مقدماتی', 'intermediate' => 'متوسط', 'advanced' => 'پیشرفته'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('level', $course->level ?? 'beginner') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">مدرس</label>
                <select name="instructor_id" class="form-select">
                    <option value="">—</option>
                    @foreach ($instructors as $instructor)
                        <option value="{{ $instructor->id }}" @selected(old('instructor_id', $course->instructor_id) == $instructor->id)>{{ $instructor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">مدت (دقیقه)</label>
                <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $course->duration_minutes ?? 0) }}" class="form-control" min="0">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">چه چیزهایی یاد می‌گیرند (هر خط یک مورد)</label>
            <textarea name="what_you_learn" rows="3" class="form-control">{{ old('what_you_learn', implode("\n", $course->what_you_learn ?? [])) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">پیش‌نیازها (هر خط یک مورد)</label>
            <textarea name="requirements" rows="2" class="form-control">{{ old('requirements', implode("\n", $course->requirements ?? [])) }}</textarea>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $product->is_published))>
            <label class="form-check-label" for="is_published">منتشر شده</label>
        </div>
        <button type="submit" class="btn btn-primary">ذخیره دوره</button>
    </div>
</form>

@if ($product->exists && $course->exists)
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">افزودن فصل</h5>
            <form method="POST" action="{{ route('admin.courses.sections.store', $product) }}" class="row g-2">
                @csrf
                <div class="col-md-8">
                    <input type="text" name="title" class="form-control" placeholder="عنوان فصل" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="sort_order" class="form-control" placeholder="ترتیب" value="0">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">افزودن</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($course->sections as $section)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ $section->title }}</strong>
                <form method="POST" action="{{ route('admin.courses.sections.destroy', [$product, $section]) }}" onsubmit="return confirm('فصل حذف شود؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-label-danger">حذف فصل</button>
                </form>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-3">
                    @foreach ($section->lessons as $lesson)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span>{{ $lesson->title }} @if($lesson->is_free_preview)<span class="badge bg-label-info">پیش‌نمایش</span>@endif</span>
                            <form method="POST" action="{{ route('admin.courses.lessons.destroy', [$product, $lesson]) }}" onsubmit="return confirm('درس حذف شود؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('admin.courses.lessons.store', [$product, $section]) }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><input type="text" name="title" class="form-control form-control-sm" placeholder="عنوان درس" required></div>
                    <div class="col-md-3"><input type="text" name="slug" class="form-control form-control-sm" placeholder="slug" dir="ltr" required></div>
                    <div class="col-md-3"><input type="text" name="video_url" class="form-control form-control-sm" placeholder="لینک ویدیو" dir="ltr"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-primary w-100">افزودن درس</button></div>
                </form>
            </div>
        </div>
    @endforeach
@endif
@endsection
