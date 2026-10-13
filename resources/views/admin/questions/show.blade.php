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
@php
    $officialAnswer = $question->answers->firstWhere('is_official', true);
    $communityAnswers = $question->answers->where('is_official', false)->values();
@endphp

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

@if (admin_can('questions', 'update'))
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">تغییر وضعیت</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.questions.update-status', $question) }}" id="cafeStatusForm">
                @csrf
                @method('PATCH')
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="status">وضعیت سوال</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach (App\Models\CafeQuestion::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $question->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5" id="rejectionReasonWrap" @style(['display: none' => old('status', $question->status) !== App\Models\CafeQuestion::STATUS_REJECTED])>
                        <label class="form-label" for="status_rejection_reason">علت رد</label>
                        <textarea name="rejection_reason" id="status_rejection_reason" rows="2" class="form-control @error('rejection_reason') is-invalid @enderror">{{ old('rejection_reason', $question->rejection_reason) }}</textarea>
                        @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-label-primary w-100">ذخیره وضعیت</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($officialAnswer && admin_can('questions', 'update'))
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">ویرایش پاسخ رسمی</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.questions.update-answer', $question) }}" id="cafeOfficialAnswerForm">
                @csrf
                @method('PUT')
                <x-admin.ckeditor
                    name="body"
                    id="official_body"
                    label="متن پاسخ"
                    :value="old('body', $officialAnswer->body)"
                    :rows="10"
                    toolbar="basic"
                />
                <button type="submit" class="btn btn-primary">ذخیره تغییرات پاسخ</button>
            </form>
        </div>
    </div>
@elseif ($officialAnswer)
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">پاسخ رسمی</h5></div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <strong>{{ $officialAnswer->user?->displayName() ?? '—' }}</strong>
                <small class="text-muted">{{ $officialAnswer->created_at->format('Y/m/d H:i') }}</small>
            </div>
            <div class="admin-prose admin-prose--rich">{!! clean_html($officialAnswer->body) !!}</div>
        </div>
    </div>
@endif

@if ($communityAnswers->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">پاسخ‌های کاربران</h5></div>
        <div class="card-body">
            @foreach ($communityAnswers as $answer)
                <div class="mb-3 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                        <div>
                            <strong>{{ $answer->user?->displayName() ?? '—' }}</strong>
                            <small class="text-muted d-block">{{ $answer->created_at->format('Y/m/d H:i') }}</small>
                        </div>
                        @if (admin_can('questions', 'delete'))
                            <form method="POST" action="{{ route('admin.questions.answers.destroy', [$question, $answer]) }}" onsubmit="return confirm('این پاسخ حذف شود؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                            </form>
                        @endif
                    </div>
                    <div class="admin-prose">{{ $answer->body }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if ($question->isPending() && ! $officialAnswer && admin_can('questions', 'update'))
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">پاسخ رسمی</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.questions.answer', $question) }}" id="cafeOfficialAnswerForm">
                        @csrf
                        <x-admin.ckeditor
                            name="body"
                            id="official_body"
                            label="متن پاسخ"
                            :value="old('body')"
                            :rows="10"
                            toolbar="basic"
                        />
                        <button type="submit" class="btn btn-primary">ثبت پاسخ و انتشار</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
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

@section('page-script')
@if (admin_can('questions', 'update'))
<script>
(() => {
  const status = document.getElementById('status');
  const wrap = document.getElementById('rejectionReasonWrap');
  if (!status || !wrap) return;

  const sync = () => {
    wrap.style.display = status.value === 'rejected' ? '' : 'none';
  };

  status.addEventListener('change', sync);
  sync();
})();
</script>
@endif
@endsection
