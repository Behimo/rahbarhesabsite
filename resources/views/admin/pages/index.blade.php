@extends('layouts.admin')

@section('title', 'صفحات')

@section('lede', 'صفحات سیستمی و داینامیک سایت')

@section('actions')
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i>صفحه جدید
    </a>
@endsection

@section('content')

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>صفحه</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $page->title }}</div>
                            <small class="text-muted" dir="ltr">{{ $page->is_system ? '/'.$page->slug : '/p/'.$page->slug }}</small>
                        </td>
                        <td>
                            @if ($page->is_system)
                                <span class="badge bg-label-secondary">سیستمی</span>
                            @else
                                <span class="badge bg-label-info">داینامیک</span>
                            @endif
                        </td>
                        <td>
                            @if ($page->is_published)
                                <span class="badge bg-label-success">منتشر شده</span>
                            @else
                                <span class="badge bg-label-warning">پیش‌نویس</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.pages.builder', $page) }}" class="btn btn-sm btn-label-primary">صفحه‌ساز</a>
                            <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-label-secondary">تنظیمات</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
