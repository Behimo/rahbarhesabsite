@include('theme::partials.home.courses', [
    'courses' => $courses,
    'title' => $title ?? 'جدیدترین دوره های ما',
    'sectionId' => $sectionId ?? 'latestCourses',
    'sectionClass' => $sectionClass ?? '',
    'isFree' => $isFree ?? false,
])
