@extends('layouts.admin')

@section('title', 'پیام‌ها')

@section('lede', 'پیام‌های دریافتی از فرم تماس سایت')

@section('content')

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>موضوع</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($messages as $message)
                    <tr class="{{ ! $message->is_read ? 'table-warning' : '' }}">
                        <td class="fw-medium">{{ $message->name }}</td>
                        <td class="text-muted">{{ $message->subject ?? Str::limit($message->message, 40) }}</td>
                        <td class="text-muted">{{ $message->created_at->format('Y/m/d H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-sm btn-label-primary">مشاهده</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">پیامی نیست.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $messages->links() }}</div>
@endsection
