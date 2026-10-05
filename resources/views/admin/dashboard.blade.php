@extends('layouts.admin')

@section('title', 'داشبورد')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block mb-1 text-muted">صفحات</span>
                        <h3 class="mb-0">{{ $stats['pages'] }}</h3>
                    </div>
                    <span class="badge bg-label-primary rounded p-2"><i class="ti ti-file-text ti-md"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block mb-1 text-muted">مقالات</span>
                        <h3 class="mb-0">{{ $stats['posts'] }}</h3>
                    </div>
                    <span class="badge bg-label-info rounded p-2"><i class="ti ti-article ti-md"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block mb-1 text-muted">محصولات</span>
                        <h3 class="mb-0">{{ $stats['products'] }}</h3>
                    </div>
                    <span class="badge bg-label-success rounded p-2"><i class="ti ti-package ti-md"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block mb-1 text-muted">پیام جدید</span>
                        <h3 class="mb-0 text-primary">{{ $stats['messages'] }}</h3>
                    </div>
                    <span class="badge bg-label-warning rounded p-2"><i class="ti ti-mail ti-md"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block mb-1 text-muted">کل پیام‌ها</span>
                        <h3 class="mb-0">{{ $stats['total_messages'] }}</h3>
                    </div>
                    <span class="badge bg-label-secondary rounded p-2"><i class="ti ti-messages ti-md"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">دسترسی سریع</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if (admin_can('pages', 'update'))
                    <div class="col-sm-6">
                        <a href="{{ route('admin.home.edit') }}" class="d-block border rounded p-3 text-body hover-shadow transition-all">
                            <div class="fw-medium mb-1">صفحه اصلی</div>
                            <small class="text-muted">هیرو، آمار، نظرات</small>
                        </a>
                    </div>
                    @endif
                    @if (admin_can('posts', 'create'))
                    <div class="col-sm-6">
                        <a href="{{ route('admin.posts.create') }}" class="d-block border rounded p-3 text-body hover-shadow transition-all">
                            <div class="fw-medium mb-1">مقاله جدید</div>
                            <small class="text-muted">بلاگ</small>
                        </a>
                    </div>
                    @endif
                    @if (admin_can('pages', 'create'))
                    <div class="col-sm-6">
                        <a href="{{ route('admin.pages.create') }}" class="d-block border rounded p-3 text-body hover-shadow transition-all">
                            <div class="fw-medium mb-1">صفحه جدید</div>
                            <small class="text-muted">صفحات داینامیک</small>
                        </a>
                    </div>
                    @endif
                    @if (admin_can('media', 'view'))
                    <div class="col-sm-6">
                        <a href="{{ route('admin.media.index') }}" class="d-block border rounded p-3 text-body hover-shadow transition-all">
                            <div class="fw-medium mb-1">رسانه</div>
                            <small class="text-muted">آپلود تصویر</small>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (admin_can('messages', 'view'))
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">آخرین پیام‌ها</h5>
            </div>
            <div class="card-body">
                @if ($recentMessages->isEmpty())
                    <p class="text-muted mb-0">پیامی دریافت نشده است.</p>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach ($recentMessages as $msg)
                            <li class="mb-3 pb-3 border-bottom">
                                <a href="{{ route('admin.messages.show', $msg) }}" class="d-flex justify-content-between align-items-start text-body">
                                    <div>
                                        <div class="fw-medium">{{ $msg->name }}</div>
                                        <small class="text-muted">{{ $msg->subject ?? Str::limit($msg->message, 50) }}</small>
                                    </div>
                                    <small class="text-muted">{{ $msg->created_at->diffForHumans() }}</small>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
