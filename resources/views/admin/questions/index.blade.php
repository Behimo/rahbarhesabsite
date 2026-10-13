@extends('layouts.admin')

@section('title', 'کافه سوال')

@section('lede', 'سوالات کاربران برای پاسخ یا رد توسط کارشناسان')

@section('actions')
    @if (admin_can('taxonomies', 'view'))
        <a href="{{ route('admin.categories.index', ['type' => 'cafe']) }}" class="btn btn-label-secondary">دسته‌بندی‌ها</a>
    @endif
@endsection

@section('content')

<form method="GET" action="{{ route('admin.questions.index') }}" class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">جستجو</label>
                <input type="search" name="q" id="q" value="{{ $q }}" class="form-control" placeholder="عنوان یا متن سوال">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">وضعیت</label>
                <select name="status" id="status" class="form-select">
                    <option value="">همه</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="category">دسته</label>
                <select name="category" id="category" class="form-select">
                    <option value="">همه</option>
                    @foreach ($cafeCategories as $cat)
                        <option value="{{ $cat->id }}" @selected($categoryId === $cat->id)>
                            {{ str_repeat('— ', $cat->treeDepth) }}{{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">اعمال فیلتر</button>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>دسته</th>
                    <th>کاربر</th>
                    <th>وضعیت</th>
                    <th>جواب‌ها</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($questions as $question)
                    <tr class="{{ $question->isPending() ? 'table-warning' : '' }}">
                        <td class="fw-medium">{{ Str::limit($question->title, 50) }}</td>
                        <td class="text-muted">{{ $question->category?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $question->user?->displayName() ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $question->isPending() ? 'bg-label-warning' : ($question->isAnswered() ? 'bg-label-success' : 'bg-label-danger') }}">
                                {{ $question->statusLabel() }}
                            </span>
                        </td>
                        <td class="text-muted">{{ fa_digits($question->answers_count) }}</td>
                        <td class="text-muted">{{ $question->created_at->format('Y/m/d H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.questions.show', $question) }}" class="btn btn-sm btn-label-primary">مشاهده</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">سوالی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $questions->links() }}</div>
@endsection
