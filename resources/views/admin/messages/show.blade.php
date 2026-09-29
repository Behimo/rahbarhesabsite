@extends('layouts.admin')

@section('title', 'مشاهده پیام')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.messages.index') }}" class="text-muted"><i class="ti ti-arrow-right me-1"></i>بازگشت</a>
    <h4 class="mt-2 mb-0">پیام از {{ $message->name }}</h4>
</div>

<div class="card" style="max-width: 42rem;">
    <div class="card-body">
        <div class="row g-3 mb-4">
            @if ($message->email)
                <div class="col-sm-6">
                    <small class="text-muted d-block">ایمیل</small>
                    <a href="mailto:{{ $message->email }}" dir="ltr">{{ $message->email }}</a>
                </div>
            @endif
            @if ($message->phone)
                <div class="col-sm-6">
                    <small class="text-muted d-block">تلفن</small>
                    <span dir="ltr">{{ $message->phone }}</span>
                </div>
            @endif
            @if ($message->subject)
                <div class="col-12">
                    <small class="text-muted d-block">موضوع</small>
                    {{ $message->subject }}
                </div>
            @endif
            <div class="col-12">
                <small class="text-muted d-block">تاریخ</small>
                {{ $message->created_at->format('Y/m/d H:i') }}
            </div>
        </div>
        <hr>
        <div class="lh-lg" style="white-space: pre-wrap;">{{ $message->message }}</div>
        <hr>
        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('حذف شود؟')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-label-danger">حذف پیام</button>
        </form>
    </div>
</div>
@endsection
