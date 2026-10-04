@extends('layouts.admin')

@section('title', 'تاریخچه صفحه')

@section('heading', 'تاریخچه: '.$page->title)

@section('content')

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>زمان</th>
                    <th>ادمین</th>
                    <th>یادداشت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($revisions as $revision)
                    <tr>
                        <td>{{ $revision->created_at->format('Y/m/d H:i') }}</td>
                        <td>{{ $revision->admin->name ?? '-' }}</td>
                        <td>{{ $revision->note ?: '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.pages.revisions.restore', [$page, $revision]) }}" onsubmit="return confirm('این نسخه بازگردانی شود؟')">
                                @csrf
                                <button class="btn btn-sm btn-label-primary">بازگردانی</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">نسخه‌ای ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
