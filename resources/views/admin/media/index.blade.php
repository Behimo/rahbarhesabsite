@extends('layouts.admin')

@section('title', 'رسانه')

@section('lede', 'آپلود و مدیریت فایل‌های تصویری')

@section('content')
<div class="card mb-4">
    @if (admin_can('media', 'create'))
        <div class="card-header"><h5 class="mb-0">آپلود فایل</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label">فایل (تصویر یا PDF، حداکثر ۵ مگ)</label>
                    <input type="file" name="file" accept="image/*,.pdf" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">متن جایگزین</label>
                    <input type="text" name="alt" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">آپلود</button>
                </div>
            </form>
        </div>
    @endif
</div>

<div class="row g-3">
    @foreach ($media as $item)
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="card-body p-3">
                    @if ($item->isImage())
                        <img src="{{ $item->url() }}" alt="{{ $item->alt }}" class="w-100 rounded mb-2" style="height: 8rem; object-fit: cover;">
                    @else
                        <div class="bg-label-secondary rounded d-flex align-items-center justify-content-center mb-2" style="height: 8rem;">PDF</div>
                    @endif
                    <p class="small text-truncate mb-2" title="{{ $item->filename }}">{{ $item->filename }}</p>
                    <input type="text" readonly value="{{ $item->url() }}" class="form-control form-control-sm mb-2" dir="ltr" onclick="this.select()">
                    @if (admin_can('media', 'delete'))
                        <form method="POST" action="{{ route('admin.media.destroy', $item) }}" onsubmit="return confirm('حذف شود؟')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-label-danger w-100">حذف</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-4">{{ $media->links() }}</div>
@endsection
