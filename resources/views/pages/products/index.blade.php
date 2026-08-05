@extends('layouts.site')

@section('page')
    <x-page-hero
        badge="محصولات"
        title="راه‌حل‌های آماده بیسان"
        subtitle="۴ محصول تست‌شده — هر کدام را جدا یا با هم استفاده کنید."
    />

    <section class="landing-section pb-20">
        <div class="landing-container">
            <x-breadcrumb :items="[
                ['name' => 'خانه', 'url' => route('home')],
                ['name' => 'محصولات', 'url' => route('products.index')],
            ]" />

            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4 lg:gap-8">
                @foreach ($products as $product)
                    <x-product-card
                        :title="$product['title']"
                        :subtitle="$product['subtitle']"
                        :description="$product['description']"
                        :accent="$product['accent']"
                        :visual="$product['visual']"
                        :features="$product['features']"
                        :href="$product['href']"
                        :audience="$product['audience']"
                        :cta="$product['cta']"
                        :featured="($product['is_featured'] ?? false)"
                        :dashboard-image="$product['dashboard_image'] ?? null"
                    />
                @endforeach
            </div>

            @if (!empty($comparison))
                <div class="mt-16">
                    <x-section-header
                        badge="مقایسه"
                        title="کدام محصول"
                        highlight="چه کاری می‌کند؟"
                        subtitle="یک نگاه سریع برای انتخاب درست."
                    />
                    <div class="mt-8 overflow-x-auto">
                        <table class="w-full min-w-[720px] text-sm">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="py-4 pr-4 text-right text-slate-400 font-medium">قابلیت</th>
                                    <th class="py-4 px-4 text-center text-bisan-orange font-semibold">راهبر</th>
                                    <th class="py-4 px-4 text-center text-purple-400 font-semibold">نوژارو</th>
                                    <th class="py-4 px-4 text-center text-emerald-400 font-semibold">ویلئو</th>
                                    <th class="py-4 px-4 text-center text-blue-400 font-semibold">افزونه</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($comparison as $row)
                                    <tr class="border-b border-white/5">
                                        <td class="py-4 pr-4 text-slate-300">{{ $row['feature'] }}</td>
                                        @foreach (['rahbar', 'nojaro', 'vileo', 'wordpress'] as $key)
                                            <td class="py-4 px-4 text-center">
                                                @if ($row[$key])
                                                    <svg class="h-5 w-5 mx-auto text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                @else
                                                    <span class="text-slate-600">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mt-12 glass-card-3d p-8 text-center">
                <h3 class="text-xl font-semibold text-white mb-2">نمی‌دانید کدام را انتخاب کنید؟</h3>
                <p class="text-slate-400 mb-6 max-w-lg mx-auto">یک تماس کافی است. نیاز شما را می‌شنویم و بهترین راه را پیشنهاد می‌دهیم — رایگان و بدون تعهد.</p>
                <a href="{{ route('contact') }}" class="btn-primary">مشاوره رایگان</a>
            </div>
        </div>
    </section>
@endsection
