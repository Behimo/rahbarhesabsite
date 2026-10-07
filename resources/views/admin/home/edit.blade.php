@extends('layouts.admin')

@section('title', 'صفحه اصلی')

@section('heading', 'ویرایش صفحه اصلی')

@section('lede', 'متن معرفی و سوالات در ستون اصلی است. عنوان بخش‌ها و دکمه ذخیره کنار صفحه می‌ماند.')

@section('content')

<form method="POST" action="{{ route('admin.home.update') }}">
    @csrf @method('PUT')

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">معرفی</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="home-heading-small">عنوان کوتاه</label>
                        <input id="home-heading-small" type="text" name="heading_small" value="{{ old('heading_small', $content['about']['heading_small'] ?? '') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="home-heading">عنوان اصلی</label>
                        <input id="home-heading" type="text" name="heading" value="{{ old('heading', $content['about']['heading'] ?? '') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="home-description">توضیح</label>
                        <textarea id="home-description" name="description" rows="4" class="form-control">{{ old('description', $content['about']['description'] ?? '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="home-banner">تصویر بنر</label>
                        <input id="home-banner" type="text" name="banner_image" value="{{ old('banner_image', $content['about']['banner_image'] ?? '') }}" class="form-control" dir="ltr" placeholder="/site/images/...">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="home-app">لینک دانلود اپ</label>
                        <input id="home-app" type="text" name="app_download_url" value="{{ old('app_download_url', $content['app_download_url'] ?? '') }}" class="form-control" dir="ltr">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">سوالات پرتکرار</h5></div>
                <div class="card-body">
                    @for ($i = 0; $i < 6; $i++)
                        <div class="{{ $i > 0 ? 'border-top pt-3 mt-3' : '' }}">
                            <div class="mb-2">
                                <label class="form-label" for="faq-q-{{ $i }}">سوال {{ $i + 1 }}</label>
                                <input id="faq-q-{{ $i }}" type="text" name="faqs[{{ $i }}][q]" value="{{ old("faqs.$i.q", $content['faqs'][$i]['q'] ?? '') }}" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="faq-a-{{ $i }}">پاسخ</label>
                                <textarea id="faq-a-{{ $i }}" name="faqs[{{ $i }}][a]" rows="2" class="form-control">{{ old("faqs.$i.a", $content['faqs'][$i]['a'] ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="form-label" for="faq-href-{{ $i }}">لینک</label>
                                <input id="faq-href-{{ $i }}" type="text" name="faqs[{{ $i }}][href]" value="{{ old("faqs.$i.href", $content['faqs'][$i]['href'] ?? '') }}" class="form-control" dir="ltr" placeholder="{{ route('blog.index') }}">
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">عنوان بخش‌ها</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="home-courses-title">دوره‌ها</label>
                        <input id="home-courses-title" type="text" name="courses_title" value="{{ old('courses_title', $content['sections']['courses_title'] ?? '') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="home-free-title">دوره‌های رایگان</label>
                        <input id="home-free-title" type="text" name="free_courses_title" value="{{ old('free_courses_title', $content['sections']['free_courses_title'] ?? '') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="home-news-title">اخبار</label>
                        <input id="home-news-title" type="text" name="news_title" value="{{ old('news_title', $content['sections']['news_title'] ?? '') }}" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ذخیره صفحه اصلی</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
