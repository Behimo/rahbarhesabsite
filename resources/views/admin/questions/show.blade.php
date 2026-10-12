@extends('layouts.admin')

@section('title', 'مشاهده سوال')

@section('heading', $question->title)

@section('actions')
    @if (admin_can('questions', 'delete'))
        <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" onsubmit="return confirm('حذف شود؟')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-label-danger">حذف سوال</button>
        </form>
    @endif
@endsection

@section('content')
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <small class="text-muted d-block">کاربر</small>
                {{ $question->user?->displayName() ?? '—' }}
            </div>
            <div class="col-sm-6">
                <small class="text-muted d-block">وضعیت</small>
                <span class="badge {{ $question->isPending() ? 'bg-label-warning' : ($question->isAnswered() ? 'bg-label-success' : 'bg-label-danger') }}">
                    {{ $question->statusLabel() }}
                </span>
            </div>
            <div class="col-sm-6">
                <small class="text-muted d-block">تاریخ ثبت</small>
                {{ $question->created_at->format('Y/m/d H:i') }}
            </div>
            @if ($question->isAnswered())
                <div class="col-sm-6">
                    <small class="text-muted d-block">لینک عمومی</small>
                    <a href="{{ route('cafe-soal.show', $question->slug) }}" target="_blank" rel="noopener">مشاهده در سایت</a>
                </div>
            @endif
            @if ($question->isRejected() && $question->rejection_reason)
                <div class="col-12">
                    <small class="text-muted d-block">علت رد</small>
                    {{ $question->rejection_reason }}
                </div>
            @endif
        </div>
        <hr>
        <div class="admin-prose">{{ $question->body }}</div>
    </div>
</div>

@if ($question->answers->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">پاسخ‌ها</h5></div>
        <div class="card-body">
            @foreach ($question->answers->sortByDesc('is_official') as $answer)
                <div class="mb-3 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>
                            {{ $answer->user?->displayName() ?? '—' }}
                            @if ($answer->is_official)
                                <span class="badge bg-label-primary">پاسخ رسمی</span>
                            @endif
                        </strong>
                        <small class="text-muted">{{ $answer->created_at->format('Y/m/d H:i') }}</small>
                    </div>
                    <div class="admin-prose">{{ $answer->body }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if ($question->isPending() && admin_can('questions', 'update'))
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">پاسخ رسمی</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.questions.answer', $question) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="body">متن پاسخ</label>
                            <textarea name="body" id="body" rows="6" class="form-control @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">ثبت پاسخ و انتشار</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">رد سوال</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.questions.reject', $question) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="rejection_reason">علت رد</label>
                            <textarea name="rejection_reason" id="rejection_reason" rows="6" class="form-control @error('rejection_reason') is-invalid @enderror" required>{{ old('rejection_reason') }}</textarea>
                            @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-label-danger">رد سوال</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
