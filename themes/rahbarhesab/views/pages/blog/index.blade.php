@extends('theme::layouts.site')

@section('page')
<section class="bg-teal-800 py-12 text-white">
    <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold">اخبار و مقالات</h1>
        <p class="mt-2 text-teal-100">راهنماها و بخشنامه‌های حسابداری و مالیات</p>
    </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @if ($posts->isEmpty())
        <p class="text-center text-slate-500">به‌زودی مقالات جدید منتشر می‌شود.</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="rh-card block overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-5">
                        @if ($post->category)
                            <span class="text-xs font-semibold text-teal-700">{{ $post->category->name }}</span>
                        @endif
                        <h2 class="mt-2 font-bold text-slate-800">{{ $post->title }}</h2>
                        <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $post->excerpt }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-10">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
