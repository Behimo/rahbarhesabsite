@extends('layouts.admin')

@section('title', 'صفحه اصلی')

@section('content')
<div class="mb-4">
    <h4 class="mb-1">ویرایش صفحه اصلی</h4>
    <p class="text-muted mb-0">چیدمان صفحه ثابت است. اینجا فقط متن معرفی، عنوان بخش‌ها و سوالات پرتکرار عوض می‌شود. دوره‌ها از فهرست دوره‌ها و اخبار از بلاگ می‌آیند.</p>
</div>

<form method="POST" action="{{ route('admin.home.update') }}">
    @csrf @method('PUT')

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">معرفی</h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">عنوان کوتاه</label>
                <input type="text" name="heading_small" value="{{ old('heading_small', $content['about']['heading_small'] ?? '') }}" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">عنوان اصلی</label>
                <input type="text" name="heading" value="{{ old('heading', $content['about']['heading'] ?? '') }}" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">توضیح</label>
                <textarea name="description" rows="4" class="form-control">{{ old('description', $content['about']['description'] ?? '') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">تصویر بنر (آدرس)</label>
                <input type="text" name="banner_image" value="{{ old('banner_image', $content['about']['banner_image'] ?? '') }}" class="form-control" dir="ltr" placeholder="/themes/rahbarhesab/images/...">
            </div>
            <div>
                <label class="form-label">لینک دانلود اپ</label>
                <input type="text" name="app_download_url" value="{{ old('app_download_url', $content['app_download_url'] ?? '') }}" class="form-control" dir="ltr">
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">عنوان بخش‌ها</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">دوره‌ها</label>
                    <input type="text" name="courses_title" value="{{ old('courses_title', $content['sections']['courses_title'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">دوره‌های رایگان</label>
                    <input type="text" name="free_courses_title" value="{{ old('free_courses_title', $content['sections']['free_courses_title'] ?? '') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">اخبار</label>
                    <input type="text" name="news_title" value="{{ old('news_title', $content['sections']['news_title'] ?? '') }}" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">سوالات پرتکرار</h5></div>
        <div class="card-body">
            @for ($i = 0; $i < 6; $i++)
                <div class="border-top pt-3 mb-3">
                    <div class="mb-2">
                        <label class="form-label">سوال {{ $i + 1 }}</label>
                        <input type="text" name="faqs[{{ $i }}][q]" value="{{ old("faqs.$i.q", $content['faqs'][$i]['q'] ?? '') }}" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">پاسخ</label>
                        <textarea name="faqs[{{ $i }}][a]" rows="2" class="form-control">{{ old("faqs.$i.a", $content['faqs'][$i]['a'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">لینک</label>
                        <input type="text" name="faqs[{{ $i }}][href]" value="{{ old("faqs.$i.href", $content['faqs'][$i]['href'] ?? '') }}" class="form-control" dir="ltr" placeholder="{{ route('blog.index') }}">
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <button type="submit" class="btn btn-primary">ذخیره صفحه اصلی</button>
</form>
@endsection
