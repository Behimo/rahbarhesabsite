@extends('layouts.panel')

@section('title', 'اعلان‌ها')

@section('content')
<h1 class="panel-title">اعلان‌ها</h1>
@if (auth()->user()->unreadNotifications->isNotEmpty())
    <form method="POST" action="{{ route('panel.notifications.read-all') }}" style="margin:-0.5rem 0 1.25rem;">
        @csrf
        <button type="submit" class="panel-inline-link">علامت‌گذاری همه به‌عنوان خوانده‌شده</button>
    </form>
@endif

@if ($notifications->isEmpty())
    <p class="panel-empty">اعلانی وجود ندارد.</p>
@else
    <div class="panel-notify">
        @foreach ($notifications as $notification)
            @php $data = $notification->data; @endphp
            <a href="{{ route('panel.notifications.read', $notification->id) }}"
               class="panel-notify__item {{ $notification->read_at ? '' : 'is-unread' }}">
                <p class="panel-notify__title">{{ $data['title'] ?? 'اعلان' }}</p>
                <p class="panel-notify__msg">{{ $data['message'] ?? '' }}</p>
                <span class="panel-notify__time">{{ fa_date($notification->created_at, 'yyyy/MM/dd HH:mm') }}</span>
            </a>
        @endforeach
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
@endif
@endsection
