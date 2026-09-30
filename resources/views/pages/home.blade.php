@extends('layouts.site')

@section('page')
    @include('partials.home.about')
    @include('partials.home.app')
    @include('partials.home.benefits')

    @include('partials.home.courses', [
        'courses' => $courses ?? collect(),
        'title' => $coursesTitle ?? 'جدیدترین دوره های ما',
        'sectionId' => 'latestCourses',
    ])

    @include('partials.home.finance')

    @if (!empty($freeCourses) && $freeCourses->isNotEmpty())
        @include('partials.home.courses', [
            'courses' => $freeCourses,
            'title' => $freeCoursesTitle ?? 'دوره های رایگان',
            'sectionId' => 'freeCourses',
            'sectionClass' => 'free-courses-section',
            'isFree' => true,
        ])
    @endif

    @if (!empty($faqs))
        @include('partials.home.faqs')
    @endif

    @include('partials.home.news')
    @include('partials.home.system')
    @include('partials.home.instagram')
    @include('partials.home.mentor')

    @if (file_exists(public_path('site/images/experience-1.jpg')))
        @include('partials.home.experiences')
    @endif

    @if (file_exists(public_path('site/images/partner-sepidar.png')))
        @include('partials.home.partners')
    @endif
@endsection
