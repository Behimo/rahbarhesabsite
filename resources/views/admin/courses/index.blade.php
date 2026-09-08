@extends('layouts.admin')

@section('title', 'دوره‌های LMS')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="mb-1">دوره‌های LMS</h4>
        <p class="text-muted mb-0">مدیریت دوره‌های آموزشی و فروش</p>
    </div>
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i>دوره جدید
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>دوره</th>
                    <th>قیمت</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td class="fw-medium">{{ $course->title }}</td>
                        <td>{{ $course->isFree() ? 'رایگان' : number_format($course->effectivePrice()).' تومان' }}</td>
                        <td>
                            @if ($course->is_published)
                                <span class="badge bg-label-success">منتشر شده</span>
                            @else
                                <span class="badge bg-label-warning">پیش‌نویس</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('courses.show', $course->slug) }}" target="_blank" class="btn btn-sm btn-label-secondary">مشاهده</a>
                            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" class="d-inline" onsubmit="return confirm('حذف شود؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">دوره‌ای ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
