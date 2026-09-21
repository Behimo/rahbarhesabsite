        <div class="rh-card mt-6 space-y-3 p-4">
            @if ($coupon ?? null)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-600">کد {{ $coupon->code }}</span>
                    <form method="POST" action="{{ route('cart.coupon.remove') }}">
                        @csrf @method('DELETE')
                        <button class="text-red-600 hover:underline">حذف</button>
                    </form>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>تخفیف:</span>
                    <span>{{ number_format($discount) }} تومان</span>
                </div>
            @else
                <form method="POST" action="{{ route('cart.coupon') }}" class="flex gap-2" x-data="{ code: '' }">
                    @csrf
                    <input x-model="code" type="text" name="code" placeholder="کد تخفیف" class="flex-1 rounded-xl border border-slate-200 px-3 py-2" dir="ltr">
                    <button class="rounded-xl border border-teal-600 px-4 py-2 text-teal-700">اعمال</button>
                </form>
            @endif
            <div class="flex items-center justify-between">
                <span class="text-slate-600">مبلغ قابل پرداخت:</span>
                <span class="text-xl font-bold text-slate-800">{{ number_format($total ?? $subtotal) }} تومان</span>
            </div>
        </div>