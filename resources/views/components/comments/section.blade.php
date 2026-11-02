@props([
    'commentable',
    'type',
])

@php
    use App\Services\Comments\CommentAvailability;
    use App\Services\Comments\CommentSettings;

    $settings = app(CommentSettings::class);
    $availability = app(CommentAvailability::class);
    $open = $availability->isOpen($commentable);
    $order = $settings->get('order', 'newest') === 'oldest' ? 'asc' : 'desc';
    $perPage = max(5, $settings->int('per_page', 20));

    $comments = $commentable->comments()
        ->approved()
        ->roots()
        ->with([
            'user',
            'replies' => fn ($q) => $q->approved()->with('user')->orderBy('created_at'),
        ])
        ->orderByDesc('is_pinned')
        ->orderBy('created_at', $order)
        ->paginate($perPage, ['*'], 'comments_page')
        ->withQueryString();
@endphp

<section id="comments" class="comments" aria-label="دیدگاه‌ها">
    <div class="comments__head">
        <h2 class="comments__title">دیدگاه‌ها</h2>
        <span class="comments__count">{{ fa_digits($comments->total()) }} دیدگاه</span>
    </div>

    @if (session('comment_success'))
        <div class="comments__flash" role="status">{{ session('comment_success') }}</div>
    @endif

    <div class="comments__list">
        @forelse ($comments as $comment)
            <x-comments.item :comment="$comment" :open="$open" :type="$type" :commentable-id="$commentable->id" />
        @empty
            <p class="comments__empty">هنوز دیدگاهی ثبت نشده است.</p>
        @endforelse
    </div>

    @if ($comments->hasPages())
        <div class="comments__pagination">
            {{ $comments->links() }}
        </div>
    @endif

    @if ($open)
        <x-comments.form :type="$type" :commentable-id="$commentable->id" />
    @else
        <p class="comments__closed">{{ $settings->get('closed_message') }}</p>
    @endif
</section>

<style>
.comments{margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid rgba(15,23,42,.08)}
.comments__head{display:flex;align-items:baseline;justify-content:space-between;gap:1rem;margin-bottom:1.25rem}
.comments__title{margin:0;font-size:1.25rem}
.comments__count{color:#64748b;font-size:.9rem}
.comments__flash{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:.75rem 1rem;border-radius:.75rem;margin-bottom:1rem}
.comments__empty,.comments__closed{color:#64748b}
.comments__item{padding:1rem 0;border-bottom:1px solid rgba(15,23,42,.06)}
.comments__meta{display:flex;flex-wrap:wrap;gap:.5rem;color:#64748b;font-size:.85rem;margin-bottom:.35rem}
.comments__author{font-weight:600;color:#0f172a}
.comments__body{white-space:pre-wrap;line-height:1.8;color:#1e293b}
.comments__replies{margin:.75rem 1rem 0 0;padding-right:1rem;border-right:2px solid #e2e8f0}
.comments__form{margin-top:1.5rem;display:grid;gap:.75rem}
.comments__form textarea,.comments__form input{width:100%;border:1px solid #cbd5e1;border-radius:.75rem;padding:.75rem .9rem}
.comments__form button{justify-self:start;background:#0f766e;color:#fff;border:0;border-radius:.75rem;padding:.65rem 1.1rem}
.comments__hp{position:absolute;left:-9999px;opacity:0;height:0;width:0;overflow:hidden}
.comments__error{color:#b91c1c;font-size:.875rem;margin:0}
.comments__badge{display:inline-block;background:#ecfeff;color:#0e7490;border-radius:999px;padding:.1rem .5rem;font-size:.75rem}
</style>
