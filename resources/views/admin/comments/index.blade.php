@extends('layouts.admin')

@section('title', 'دیدگاه‌ها')

@section('heading', 'مدیریت دیدگاه‌ها')

@section('lede', 'تأیید، رد، اسپم و پاسخ به دیدگاه‌های کاربران')

@section('actions')
    <a href="{{ route('admin.comments.settings') }}" class="btn btn-label-secondary">تنظیمات دیدگاه‌ها</a>
@endsection

@section('content')
<div class="mb-4 d-flex flex-wrap gap-2">
    @php
        $tabs = ['pending' => 'در انتظار', 'approved' => 'تأیید شده', 'spam' => 'اسپم', 'rejected' => 'رد شده', 'trash' => 'زباله‌دان', 'all' => 'همه'];
    @endphp
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.comments.index', ['status' => $key]) }}"
           class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-label-secondary' }}">
            {{ $label }}
            @if ($key !== 'all')
                <span class="badge bg-label-dark ms-1">{{ fa_digits($counts[$key] ?? 0) }}</span>
            @endif
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route('admin.comments.index') }}" class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="col-md-5">
                <label class="form-label" for="q">جستجو</label>
                <input type="search" name="q" id="q" value="{{ request('q') }}" class="form-control" placeholder="متن، نام یا ایمیل">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="type">نوع محتوا</label>
                <select name="type" id="type" class="form-select">
                    <option value="">همه</option>
                    <option value="post" @selected(request('type') === 'post')>مقاله</option>
                    <option value="product" @selected(request('type') === 'product')>دوره</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">اعمال</button>
            </div>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('admin.comments.bulk') }}" class="card">
    @csrf
    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <select name="action" class="form-select form-select-sm w-auto" required>
            <option value="">عملیات گروهی</option>
            <option value="approve">تأیید</option>
            <option value="reject">رد</option>
            <option value="spam">اسپم</option>
            <option value="trash">زباله‌دان</option>
            <option value="destroy">حذف قطعی</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">اجرا</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" onclick="document.querySelectorAll('.comment-check').forEach(c => c.checked = this.checked)"></th>
                    <th>نویسنده</th>
                    <th>متن</th>
                    <th>محتوا</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($comments as $comment)
                    <tr>
                        <td><input type="checkbox" class="comment-check" name="ids[]" value="{{ $comment->id }}"></td>
                        <td>
                            <div class="fw-medium">{{ $comment->displayName() }}</div>
                            <div class="text-muted small">{{ $comment->author_ip }}</div>
                        </td>
                        <td>
                            <div>{{ Str::limit($comment->body, 120) }}</div>
                            @if ($comment->spam_reasons)
                                <div class="small text-warning mt-1">دلایل: {{ implode(', ', $comment->spam_reasons) }}</div>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ class_basename($comment->commentable_type) }} #{{ $comment->commentable_id }}
                        </td>
                        <td><span class="badge bg-label-secondary">{{ $statuses[$comment->status] ?? $comment->status }}</span></td>
                        <td class="text-muted small">{{ $comment->created_at?->format('Y/m/d H:i') }}</td>
                        <td class="text-nowrap">
                            @if ($comment->status !== 'approved')
                                <form method="POST" action="{{ route('admin.comments.approve', $comment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">تأیید</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.comments.spam', $comment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-warning">اسپم</button></form>
                            <form method="POST" action="{{ route('admin.comments.trash', $comment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-label-secondary">زباله</button></form>
                            <form method="POST" action="{{ route('admin.comments.pin', $comment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-label-primary">{{ $comment->is_pinned ? 'برداشتن پین' : 'پین' }}</button></form>
                            <form method="POST" action="{{ route('admin.comments.block-ip', $comment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-label-danger">مسدود IP</button></form>
                            @if ($comment->user_id)
                                <form method="POST" action="{{ route('admin.comments.ban-user', $comment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-label-danger">منع کاربر</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.comments.reply', $comment) }}" class="d-inline-flex gap-1 mt-1">
                                @csrf
                                <input type="text" name="body" class="form-control form-control-sm" placeholder="پاسخ ادمین" required>
                                <button class="btn btn-sm btn-primary">پاسخ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">دیدگاهی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($comments->hasPages())
        <div class="card-footer">{{ $comments->links() }}</div>
    @endif
</form>
@endsection
