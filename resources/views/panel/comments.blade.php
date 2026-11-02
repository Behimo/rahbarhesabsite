@extends('layouts.panel')

@section('title', 'دیدگاه‌های من')

@section('content')
<h1 class="panel-title">دیدگاه‌های من</h1>

@if ($comments->isEmpty())
    <p class="panel-empty">هنوز دیدگاهی ثبت نکرده‌اید.</p>
@else
    <div class="panel-table-wrap">
        <table class="panel-table">
            <thead>
                <tr>
                    <th>متن</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comments as $comment)
                    <tr>
                        <td>{{ \Illuminate\Support\Str::limit($comment->body, 80) }}</td>
                        <td>
                            <span class="panel-status {{ $comment->isApproved() ? 'panel-status--ok' : ($comment->status === 'rejected' || $comment->status === 'spam' ? 'panel-status--error' : 'panel-status--wait') }}">
                                {{ \App\Models\Comment::statusLabels()[$comment->status] ?? $comment->status }}
                            </span>
                        </td>
                        <td>{{ fa_date($comment->created_at) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $comments->links() }}</div>
@endif
@endsection
