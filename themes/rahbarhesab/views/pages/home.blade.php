@extends('theme::layouts.site')

@section('page')
    @include('theme::partials.home.about')
    @include('theme::partials.home.app')
    @include('theme::partials.home.benefits')

    @include('theme::partials.home.courses', [
        'courses' => $courses,
        'title' => 'جدیدترین دوره های ما',
        'sectionId' => 'latestCourses',
    ])

    @include('theme::partials.home.finance')

    @if (!empty($freeCourses) && $freeCourses->isNotEmpty())
        @include('theme::partials.home.courses', [
            'courses' => $freeCourses,
            'title' => 'دوره های رایگان',
            'sectionId' => 'freeCourses',
            'sectionClass' => 'free-courses-section',
            'isFree' => true,
        ])
    @endif

    @if (!empty($faqs))
        @include('theme::partials.home.faqs')
    @endif

    @include('theme::partials.home.news')
    @include('theme::partials.home.system')
    @include('theme::partials.home.instagram')
    @include('theme::partials.home.mentor')

    @if (file_exists(public_path('themes/rahbarhesab/images/experience-1.jpg')))
        @include('theme::partials.home.experiences')
    @endif

    @if (file_exists(public_path('themes/rahbarhesab/images/partner-sepidar.png')))
        @include('theme::partials.home.partners')
    @endif
@endsection
