@props([
    'comment',
    'open' => false,
    'type',
    'commentableId',
])

<article class="comments__item" id="comment-{{ $comment->id }}">
    <div class="comments__meta">
        <span class="comments__author">{{ $comment->displayName() }}</span>
        @if ($comment->is_admin_reply)
            <span class="comments__badge">پاسخ رسمی</span>
        @endif
        @if ($comment->is_pinned)
            <span class="comments__badge">سنجاق‌شده</span>
        @endif
        <time datetime="{{ $comment->created_at?->toIso8601String() }}">{{ $comment->created_at?->format('Y/m/d H:i') }}</time>
    </div>
    <div class="comments__body">{!! nl2br(e($comment->body)) !!}</div>

    @if ($open && $comment->parent_id === null)
        <details class="comments__reply-toggle">
            <summary>پاسخ</summary>
            <x-comments.form :type="$type" :commentable-id="$commentableId" :parent-id="$comment->id" />
        </details>
    @endif

    @if ($comment->relationLoaded('replies') && $comment->replies->isNotEmpty())
        <div class="comments__replies">
            @foreach ($comment->replies as $reply)
                <article class="comments__item" id="comment-{{ $reply->id }}">
                    <div class="comments__meta">
                        <span class="comments__author">{{ $reply->displayName() }}</span>
                        @if ($reply->is_admin_reply)
                            <span class="comments__badge">پاسخ رسمی</span>
                        @endif
                        <time datetime="{{ $reply->created_at?->toIso8601String() }}">{{ $reply->created_at?->format('Y/m/d H:i') }}</time>
                    </div>
                    <div class="comments__body">{!! nl2br(e($reply->body)) !!}</div>
                </article>
            @endforeach
        </div>
    @endif
</article>
