@extends('layouts.admin')

@php
    $level = old('level', $course->level ?? 'beginner');
    $levels = ['beginner' => 'مقدماتی', 'intermediate' => 'متوسط', 'advanced' => 'پیشرفته'];
@endphp

@section('title', $product->exists ? 'ویرایش دوره' : 'دوره جدید')

@section('heading', $product->exists ? 'ویرایش: '.$product->title : 'دوره جدید')

@section('lede', 'شرح دوره و دسته‌بندی در ستون اصلی است. قیمت، سطح و انتشار کنار آن می‌ماند.')

@section('vendor-style')
@include('admin.partials.composer-styles')
@endsection

@section('content')

<form method="POST" action="{{ $product->exists ? route('admin.courses.update', $product) : route('admin.courses.store') }}" class="mb-4">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">شرح دوره</h5></div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="course-title">عنوان *</label>
                            <input id="course-title" type="text" name="title" value="{{ old('title', $product->title) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="course-slug">نامک *</label>
                            <input id="course-slug" type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control" dir="ltr" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="course-subtitle">زیرعنوان</label>
                        <input id="course-subtitle" type="text" name="subtitle" value="{{ old('subtitle', $product->subtitle) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="course-description">توضیحات</label>
                        <textarea id="course-description" name="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="course-learn">چه چیزهایی یاد می‌گیرند</label>
                        <textarea id="course-learn" name="what_you_learn" rows="3" class="form-control">{{ old('what_you_learn', implode("\n", $course->what_you_learn ?? [])) }}</textarea>
                        <div class="form-text">هر خط یک مورد.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="course-requirements">پیش‌نیازها</label>
                        <textarea id="course-requirements" name="requirements" rows="2" class="form-control">{{ old('requirements', implode("\n", $course->requirements ?? [])) }}</textarea>
                        <div class="form-text">هر خط یک مورد.</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">جایگاه در درخت محصول</h5></div>
                <div class="card-body">
                    @include('admin.partials.category-picker')
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">فروش و انتشار</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published" @checked(old('is_published', $product->is_published))>
                        <label class="form-check-label" for="is_published">منتشر شده</label>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="course-price">قیمت (تومان)</label>
                            <input id="course-price" type="number" name="price" value="{{ old('price', $product->price ?? 0) }}" class="form-control" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="course-sale">قیمت تخفیف</label>
                            <input id="course-sale" type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-label">سطح</div>
                        <div class="sb-pills">
                            @foreach ($levels as $val => $label)
                                <label class="sb-pill">
                                    <input type="radio" name="level" value="{{ $val }}" @checked($level === $val)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="course-instructor">مدرس</label>
                        <select id="course-instructor" name="instructor_id" class="form-select">
                            <option value="">بدون مدرس</option>
                            @foreach ($instructors as $instructor)
                                <option value="{{ $instructor->id }}" @selected(old('instructor_id', $course->instructor_id) == $instructor->id)>{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="course-duration">مدت (دقیقه)</label>
                        <input id="course-duration" type="number" name="duration_minutes" value="{{ old('duration_minutes', $course->duration_minutes ?? 0) }}" class="form-control" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ذخیره دوره</button>
                </div>
            </div>
        </div>
    </div>
</form>

@if ($product->exists && $course->exists)
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">افزودن فصل</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.courses.sections.store', $product) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-8">
                    <label class="form-label" for="section-title">عنوان فصل</label>
                    <input id="section-title" type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="section-order">ترتیب</label>
                    <input id="section-order" type="number" name="sort_order" class="form-control" value="0">
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
                <h5 class="mb-0">{{ $section->title }}</h5>
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
                    <div class="col-md-4"><input type="text" name="title" class="form-control form-control-sm" placeholder="عنوان درس" aria-label="عنوان درس" required></div>
                    <div class="col-md-3"><input type="text" name="slug" class="form-control form-control-sm" placeholder="slug" aria-label="نامک درس" dir="ltr" required></div>
                    <div class="col-md-3"><input type="text" name="video_url" class="form-control form-control-sm" placeholder="لینک ویدیو" aria-label="لینک ویدیو" dir="ltr"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-primary w-100">افزودن درس</button></div>
                </form>
            </div>
        </div>
    @endforeach
@endif
@endsection
