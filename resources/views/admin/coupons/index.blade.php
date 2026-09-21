@extends('layouts.admin')

@section('title', 'کدهای تخفیف')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">کدهای تخفیف</h4>
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">کد جدید</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>کد</th>
                    <th>عنوان</th>
                    <th>مقدار</th>
                    <th>مصرف</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($coupons as $coupon)
                    <tr>
                        <td dir="ltr"><code>{{ $coupon->code }}</code></td>
                        <td>{{ $coupon->title }}</td>
                        <td>
                            @if (str_starts_with($coupon->type, 'percentage'))
                                {{ rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') }}٪
                            @else
                                {{ number_format($coupon->value) }} تومان
                            @endif
                        </td>
                        <td>{{ $coupon->used_count }}{{ $coupon->usage_limit_total ? ' / '.$coupon->usage_limit_total : '' }}</td>
                        <td>
                            <span class="badge {{ $coupon->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $coupon->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="d-inline" onsubmit="return confirm('حذف شود؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-label-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">کد تخفیفی ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($coupons->hasPages())
        <div class="card-footer">{{ $coupons->links() }}</div>
    @endif
</div>
@endsection
