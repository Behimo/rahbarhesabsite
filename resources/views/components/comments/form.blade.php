@props([
    'type',
    'commentableId',
    'parentId' => null,
])

@php
    $settings = app(\App\Services\Comments\CommentSettings::class);
    $authRequired = $settings->bool('auth_required', true);
@endphp

@auth
    <form method="POST" action="{{ route('comments.store') }}" class="comments__form">
        @csrf
        <input type="hidden" name="commentable_type" value="{{ $type }}">
        <input type="hidden" name="commentable_id" value="{{ $commentableId }}">
        @if ($parentId)
            <input type="hidden" name="parent_id" value="{{ $parentId }}">
        @endif
        <div class="comments__hp" aria-hidden="true">
            <label for="website-{{ $parentId ?: 'root' }}">وب‌سایت</label>
            <input type="text" name="website" id="website-{{ $parentId ?: 'root' }}" tabindex="-1" autocomplete="off">
        </div>
        <label class="visually-hidden" for="comment-body-{{ $parentId ?: 'root' }}">متن دیدگاه</label>
        <textarea
            id="comment-body-{{ $parentId ?: 'root' }}"
            name="body"
            rows="4"
            required
            placeholder="{{ $parentId ? 'پاسخ شما…' : 'دیدگاه شما…' }}"
        >{{ old('body') }}</textarea>
        @error('body')
            <p class="comments__error" role="alert">{{ $message }}</p>
        @enderror
        <button type="submit">{{ $parentId ? 'ارسال پاسخ' : 'ارسال دیدگاه' }}</button>
    </form>
@else
    @if ($authRequired)
        <p class="comments__closed">
            برای ارسال دیدگاه
            <a href="{{ route('login', ['redirect' => url()->current().'#comments']) }}">وارد شوید</a>.
        </p>
    @else
        <form method="POST" action="{{ route('comments.store') }}" class="comments__form">
            @csrf
            <input type="hidden" name="commentable_type" value="{{ $type }}">
            <input type="hidden" name="commentable_id" value="{{ $commentableId }}">
            @if ($parentId)
                <input type="hidden" name="parent_id" value="{{ $parentId }}">
            @endif
            <div class="comments__hp" aria-hidden="true">
                <label for="website-guest-{{ $parentId ?: 'root' }}">وب‌سایت</label>
                <input type="text" name="website" id="website-guest-{{ $parentId ?: 'root' }}" tabindex="-1" autocomplete="off">
            </div>
            <input type="text" name="author_name" value="{{ old('author_name') }}" placeholder="نام" required>
            <input type="email" name="author_email" value="{{ old('author_email') }}" placeholder="ایمیل" required>
            <textarea name="body" rows="4" required placeholder="دیدگاه شما…">{{ old('body') }}</textarea>
            @error('body')
                <p class="comments__error" role="alert">{{ $message }}</p>
            @enderror
            <button type="submit">ارسال دیدگاه</button>
        </form>
    @endif
@endauth
