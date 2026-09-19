@include('theme::partials.home.faqs', [
    'faqs' => $faqs ?? $items ?? [],
    'sectionTitle' => $sectionTitle ?? $title ?? 'پرتکرارترین سوالات حسابداری',
])
