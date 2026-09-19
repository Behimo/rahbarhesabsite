@include('theme::partials.home.news', [
    'latestPosts' => $latestPosts ?? $posts ?? collect(),
    'fallbackNews' => $fallbackNews ?? [],
    'sectionTitle' => $sectionTitle ?? $title ?? 'اخبار و بخشنامه های جدید',
])
