@props(['contact' => ['email' => 'info@bisan.ir']])

<footer class="site-footer" data-sr="up">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-bisan-orange/8 to-transparent"></div>

    <div class="landing-container relative py-16">
        <div class="grid gap-12 lg:grid-cols-4">
            <div class="lg:col-span-1" data-sr="up" data-sr-group="footer">
                <a href="{{ route('home') }}" class="mb-4 inline-block">
                    <x-logo size="sm" />
                </a>
                <p class="text-sm leading-relaxed text-slate-400">
                    نرم‌افزار آماده یا پروژه اختصاصی — برای رشد کسب‌وکار شما. یک تماس، راه روشن.
                </p>
                <a href="mailto:{{ $contact['email'] }}" class="mt-4 inline-flex items-center gap-2 text-sm text-bisan-orange-light transition hover:text-bisan-orange">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ $contact['email'] }}
                </a>
            </div>

            @foreach ([
                'محصولات' => [
                    'راهبر CRM' => route('products.show', 'rahbar'),
                    'نوژارو' => route('products.show', 'nojaro'),
                    'ویلئو' => route('products.show', 'vileo'),
                    'افزونه وردپرس' => route('products.show', 'wordpress-plugin'),
                ],
                'پروژه اختصاصی' => [
                    'نرم‌افزار مخصوص شما' => route('services'),
                    'وصل کردن سیستم‌ها' => route('services'),
                    'نسخه اولیه سریع' => route('services'),
                    'تیم اختصاصی' => route('services'),
                ],
                'شرکت' => [
                    'چرا بیسان؟' => route('why-bisan'),
                    'درباره ما' => route('about'),
                    'تماس با ما' => route('contact'),
                ],
            ] as $heading => $links)
                <div data-sr="up" data-sr-group="footer">
                    <h4 class="mb-4 text-sm font-semibold text-white">{{ $heading }}</h4>
                    <ul class="space-y-3">
                        @foreach ($links as $label => $href)
                            <li>
                                <a href="{{ $href }}" class="footer-link">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row">
            <p class="text-sm text-slate-500">
                © {{ date('Y') }} BISAN Holding. تمامی حقوق محفوظ است.
            </p>
            <p class="text-sm text-slate-500">
                ساخت و توسعه در ایران
            </p>
        </div>
    </div>
</footer>
