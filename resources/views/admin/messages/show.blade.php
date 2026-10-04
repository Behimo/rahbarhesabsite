@extends('layouts.admin')

@section('title', 'مشاهده پیام')

@section('heading', 'پیام از '.$message->name)

@section('actions')
    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('حذف شود؟')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-label-danger">حذف پیام</button>
    </form>
@endsection

@section('content')
<div class="card">
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
        <div class="admin-prose">{{ $message->message }}</div>
    </div>
</div>
@endsection
