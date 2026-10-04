@extends('layouts.admin')

@section('title', 'محصولات')

@section('lede', 'مدیریت محصولات سایت')

@section('actions')
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i>محصول جدید
    </a>
@endsection

@section('content')

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>محصول</th>
                    <th>Slug</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="fw-medium">{{ $product->title }}</td>
                        <td class="text-muted" dir="ltr">{{ $product->slug }}</td>
                        <td>
                            @if ($product->is_published)
                                <span class="badge bg-label-success">منتشر شده</span>
                            @else
                                <span class="badge bg-label-warning">پیش‌نویس</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="btn btn-sm btn-label-secondary">مشاهده</a>
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('حذف شود؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">محصولی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
