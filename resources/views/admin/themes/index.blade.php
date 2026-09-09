@extends('layouts.admin')
@section('title', 'قالب‌ها')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">مدیریت قالب‌ها</h4>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.themes.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-8">
                <input type="file" name="theme_zip" class="form-control" accept=".zip" required>
            </div>
            <div class="col-md-4"><button class="btn btn-primary w-100">نصب قالب ZIP</button></div>
        </form>
    </div>
</div>
<div class="row g-4">
    @foreach($themes as $theme)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5>{{ $theme->name }}</h5>
                    <p class="text-muted mb-2">نسخه {{ $theme->version }}</p>
                    @if($theme->is_active)<span class="badge bg-success">فعال</span>@endif
                </div>
                <div class="card-footer">
                    @unless($theme->is_active)
                        <form method="POST" action="{{ route('admin.themes.activate', $theme->slug) }}">@csrf<button class="btn btn-sm btn-primary">فعال‌سازی</button></form>
                    @endunless
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
