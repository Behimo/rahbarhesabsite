@extends('layouts.admin')

@php
    $officialAnswer = $question->answers->firstWhere('is_official', true);
    $communityAnswers = $question->answers->where('is_official', false)->values();
    $canUpdate = admin_can('questions', 'update');
    $isPendingAnswer = $question->isPending() && ! $officialAnswer && $canUpdate;
@endphp

@section('title', 'ویرایش سوال')

@section('heading', $question->title)

@section('lede', 'دسته و وضعیت کنار صفحه است؛ پاسخ در ستون اصلی.')

@section('actions')
    @if ($question->isAnswered())
        <a href="{{ route('cafe-soal.show', $question->slug) }}" class="btn btn-label-primary" target="_blank" rel="noopener">مشاهده در سایت</a>
    @endif
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

@if ($canUpdate && ! $isPendingAnswer)
    <form method="POST" action="{{ route('admin.questions.update', $question) }}" id="cafeQuestionForm">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">سوال</h5></div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <small class="text-muted d-block">کاربر</small>
                                {{ $question->user?->displayName() ?? '—' }}
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">تاریخ ثبت</small>
                                {{ $question->created_at->format('Y/m/d H:i') }}
                            </div>
                        </div>
                        <hr>
                        <div class="admin-prose">{{ $question->body }}</div>
                    </div>
                </div>

                @if ($officialAnswer)
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0">پاسخ رسمی</h5></div>
                        <div class="card-body">
                            <x-admin.ckeditor
                                name="body"
                                id="official_body"
                                label="متن پاسخ"
                                :value="old('body', $officialAnswer->body)"
                                :rows="10"
                                toolbar="basic"
                            />
                        </div>
                    </div>
                @endif

                @include('admin.questions.partials.community-answers', ['question' => $question, 'communityAnswers' => $communityAnswers])
            </div>

            <div class="col-xl-4 admin-form-side">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">تنظیمات</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="category_id">دسته سوال</label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                @forelse ($cafeCategories as $cat)
                                    <option value="{{ $cat->id }}" @selected((string) old('category_id', $question->category?->id) === (string) $cat->id)>
                                        {{ str_repeat('— ', $cat->treeDepth) }}{{ $cat->name }}
                                    </option>
                                @empty
                                    <option value="" disabled selected>دسته‌ای تعریف نشده است</option>
                                @endforelse
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if (admin_can('taxonomies', 'view'))
                                <div class="form-text"><a href="{{ route('admin.categories.index', ['type' => 'cafe']) }}">مدیریت دسته‌ها</a></div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="status">وضعیت</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach (App\Models\CafeQuestion::statusLabels() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $question->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3" id="rejectionReasonWrap" @style(['display: none' => old('status', $question->status) !== App\Models\CafeQuestion::STATUS_REJECTED])>
                            <label class="form-label" for="status_rejection_reason">علت رد</label>
                            <textarea name="rejection_reason" id="status_rejection_reason" rows="3" class="form-control @error('rejection_reason') is-invalid @enderror">{{ old('rejection_reason', $question->rejection_reason) }}</textarea>
                            @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100" @disabled($cafeCategories->isEmpty())>ذخیره</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@elseif ($isPendingAnswer)
    <form method="POST" action="{{ route('admin.questions.answer', $question) }}" id="cafeOfficialAnswerForm">
        @csrf
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">سوال</h5></div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <small class="text-muted d-block">کاربر</small>
                                {{ $question->user?->displayName() ?? '—' }}
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">تاریخ ثبت</small>
                                {{ $question->created_at->format('Y/m/d H:i') }}
                            </div>
                        </div>
                        <hr>
                        <div class="admin-prose">{{ $question->body }}</div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">پاسخ رسمی</h5></div>
                    <div class="card-body">
                        <x-admin.ckeditor
                            name="body"
                            id="official_body"
                            label="متن پاسخ"
                            :value="old('body')"
                            :rows="10"
                            toolbar="basic"
                        />
                    </div>
                </div>
            </div>

            <div class="col-xl-4 admin-form-side">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">تنظیمات</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="category_id">دسته سوال</label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                @forelse ($cafeCategories as $cat)
                                    <option value="{{ $cat->id }}" @selected((string) old('category_id', $question->category?->id) === (string) $cat->id)>
                                        {{ str_repeat('— ', $cat->treeDepth) }}{{ $cat->name }}
                                    </option>
                                @empty
                                    <option value="" disabled selected>دسته‌ای تعریف نشده است</option>
                                @endforelse
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if (admin_can('taxonomies', 'view'))
                                <div class="form-text"><a href="{{ route('admin.categories.index', ['type' => 'cafe']) }}">مدیریت دسته‌ها</a></div>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary w-100" @disabled($cafeCategories->isEmpty())>ثبت پاسخ و انتشار</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">رد سوال</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="rejection_reason">علت رد</label>
                            <textarea name="rejection_reason" id="rejection_reason" form="cafeRejectForm" rows="4" class="form-control @error('rejection_reason') is-invalid @enderror" required>{{ old('rejection_reason') }}</textarea>
                            @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" form="cafeRejectForm" class="btn btn-label-danger w-100">رد سوال</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.questions.reject', $question) }}" id="cafeRejectForm" class="d-none">
        @csrf
        <input type="hidden" name="category_id" id="reject_category_id" value="{{ old('category_id', $question->category?->id) }}">
    </form>
@else
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
                    <small class="text-muted d-block">دسته</small>
                    {{ $question->category?->name ?? '—' }}
                </div>
                <div class="col-sm-6">
                    <small class="text-muted d-block">تاریخ ثبت</small>
                    {{ $question->created_at->format('Y/m/d H:i') }}
                </div>
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

    @if ($officialAnswer)
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

    @include('admin.questions.partials.community-answers', ['question' => $question, 'communityAnswers' => $communityAnswers])
@endif
@endsection

@section('page-script')
@if ($canUpdate)
<script>
(() => {
  const status = document.getElementById('status');
  const wrap = document.getElementById('rejectionReasonWrap');
  if (status && wrap) {
    const sync = () => {
      wrap.style.display = status.value === 'rejected' ? '' : 'none';
    };
    status.addEventListener('change', sync);
    sync();
  }

  const category = document.getElementById('category_id');
  const rejectCategory = document.getElementById('reject_category_id');
  if (category && rejectCategory) {
    const syncCategory = () => { rejectCategory.value = category.value; };
    category.addEventListener('change', syncCategory);
    syncCategory();
  }
})();
</script>
@endif
@endsection
