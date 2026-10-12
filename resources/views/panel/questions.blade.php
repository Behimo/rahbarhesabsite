@extends('layouts.panel')

@section('title', 'سوالات من')

@section('content')
<h1 class="panel-title">سوالات من</h1>
<p class="panel-muted" style="margin-top:-0.5rem;margin-bottom:1.25rem;">
    <a href="{{ route('cafe-soal.index', ['ask' => 1]) }}" class="panel-inline-link">پرسیدن سوال جدید</a>
    ·
    <a href="{{ route('cafe-soal.index') }}" class="panel-inline-link">مشاهده کافه سوال</a>
</p>

@if ($questions->isEmpty())
    <p class="panel-empty">هنوز سوالی نپرسیده‌اید.</p>
    <a href="{{ route('cafe-soal.index') }}" class="panel-inline-link">مشاهده کافه سوال</a>
@else
    <div class="panel-table-wrap">
        <table class="panel-table">
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($questions as $question)
                    <tr>
                        <td>{{ $question->title }}</td>
                        <td>
                            <span class="panel-status {{ $question->isAnswered() ? 'panel-status--ok' : ($question->isRejected() ? 'panel-status--error' : 'panel-status--wait') }}">
                                {{ $question->statusLabel() }}
                            </span>
                            @if ($question->isRejected() && $question->rejection_reason)
                                <p class="panel-muted mt-1 mb-0">{{ $question->rejection_reason }}</p>
                            @endif
                        </td>
                        <td>{{ fa_date($question->created_at) }}</td>
                        <td>
                            @if ($question->isPublished())
                                <a href="{{ route('cafe-soal.show', $question->slug) }}" class="panel-inline-link">مشاهده</a>
                            @else
                                <span class="panel-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $questions->links() }}</div>
@endif
@endsection
