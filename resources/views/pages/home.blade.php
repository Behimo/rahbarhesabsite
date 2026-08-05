@extends('layouts.site')

@section('page')
    <x-hero :hero="$hero ?? []" :hero-pills="$heroPills" />

    @if (!empty($trustBadges))
        <section class="trust-bar" aria-label="نشان‌های اعتماد">
            <div class="landing-container">
                <div class="trust-bar__inner">
                    @foreach ($trustBadges as $badge)
                        <div class="trust-badge" data-sr="up" data-sr-group="trust">
                            <span class="trust-badge__icon">
                                @switch($badge['icon'] ?? 'check')
                                    @case('shield')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        @break
                                    @case('users')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        @break
                                    @case('support')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @break
                                    @case('star')
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @break
                                    @default
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @endswitch
                            </span>
                            <span class="trust-badge__label">{{ $badge['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section data-section="stats" class="landing-section section-stats">
        <div class="stats-backdrop" aria-hidden="true">
            <div class="stats-globe">
                <div class="stats-globe__sphere"></div>
                <div class="stats-globe__ring stats-globe__ring--1"></div>
                <div class="stats-globe__ring stats-globe__ring--2"></div>
                <span class="stats-globe__node" style="top: 20%; right: 15%;"></span>
                <span class="stats-globe__node" style="bottom: 30%; left: 20%; animation-delay: -1s;"></span>
                <span class="stats-globe__node" style="top: 50%; right: 8%; animation-delay: -2s;"></span>
            </div>
        </div>
        <div class="landing-container relative z-10">
            <x-section-header badge="اعتماد شما" title="این‌ها" highlight="حرف ماست" subtitle="اعداد واقعی از محصولات و مشتریانی که هر روز با آن‌ها کار می‌کنیم." />
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="stat-cube" data-sr="up" data-sr-group="stats">
                        <div class="stat-cube__inner glass-card-3d p-6 text-center stat-card" x-data="counter('{{ $stat['value'] }}')">
                            <p class="mb-1 text-3xl font-extrabold text-white" x-text="display">{{ $stat['value'] }}</p>
                            <p class="text-sm font-medium text-slate-300">{{ $stat['label'] }}</p>
                            @if (!empty($stat['hint']))
                                <p class="mt-1 text-xs text-slate-500">{{ $stat['hint'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="products" data-section="products" class="landing-section section-products">
        <div class="section-glow section-glow--orange" data-parallax="-0.03"></div>
        <div class="landing-container">
            <x-section-header
                badge="محصولات ما"
                title="نرم‌افزارهایی که"
                highlight="خودمان ساخته‌ایم"
                subtitle="۴ محصول فعال — هر روز در دست بیش از ۱۲۰۰ کسب‌وکار. امتحان‌شده، مقیاس‌پذیر و آماده استفاده."
            />
            <div class="products-grid">
                @foreach ($products as $product)
                    <div
                        class="{{ ($product['is_featured'] ?? false) || $loop->first ? 'products-grid__featured' : '' }}"
                        data-sr="up"
                        data-sr-group="products"
                    >
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
                            :featured="($product['is_featured'] ?? false) || $loop->first"
                            :dashboard-image="$product['dashboard_image'] ?? null"
                            class="h-full"
                        />
                    </div>
                @endforeach
            </div>
            <div class="mt-10 text-center" data-sr="up">
                <a href="{{ route('products.index') }}" class="btn-outline">مشاهده همه محصولات و مقایسه</a>
            </div>
        </div>
    </section>

    <x-dev-showcase :capabilities="$devCapabilities" :dev-stats="$devStats" />

    <x-tech-marquee :items="$techStack" />

    <x-why-bisan :items="$whyBisan" />

    @if (!empty($testimonials))
        <section data-section="testimonials" class="landing-section section-testimonials">
            <div class="section-glow section-glow--purple" data-parallax="0.03"></div>
            <div class="landing-container">
                <x-section-header
                    badge="نظر مشتریان"
                    title="آنچه"
                    highlight="می‌گویند"
                    subtitle="تجربه واقعی کسب‌وکارهایی که با محصولات بیسان رشد کرده‌اند."
                />
                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ($testimonials as $testimonial)
                        <div class="testimonial-card glass-card-3d flex flex-col p-6" data-sr="up" data-sr-group="testimonials">
                            <div class="mb-4 flex gap-1 text-bisan-orange">
                                @for ($i = 0; $i < ($testimonial['rating'] ?? 5); $i++)
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                            <p class="flex-1 text-sm leading-8 text-slate-300">«{{ $testimonial['text'] }}»</p>
                            <div class="mt-6 border-t border-white/5 pt-4">
                                <p class="font-semibold text-white">{{ $testimonial['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $testimonial['role'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (isset($latestPosts) && $latestPosts->isNotEmpty())
        <section data-section="blog" class="landing-section section-blog-preview">
            <div class="landing-container">
                <x-section-header
                    badge="بلاگ"
                    title="آخرین"
                    highlight="مقالات"
                    subtitle="راهنماها و نکات کاربردی برای رشد کسب‌وکار."
                />
                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ($latestPosts as $post)
                        <a href="{{ route('blog.show', $post->slug) }}" class="blog-preview-card glass-card-3d group block overflow-hidden" data-sr="up" data-sr-group="blog">
                            @if ($post->featured_image)
                                <div class="aspect-video overflow-hidden">
                                    <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                </div>
                            @else
                                <div class="flex aspect-video items-center justify-center bg-gradient-to-br from-bisan-orange/20 to-bisan-purple/20">
                                    <span class="text-4xl font-bold text-white/20">B</span>
                                </div>
                            @endif
                            <div class="p-5">
                                @if ($post->category)
                                    <span class="text-xs text-bisan-orange">{{ $post->category->name }}</span>
                                @endif
                                <h3 class="mt-2 font-semibold text-white transition group-hover:text-bisan-orange-light">{{ $post->title }}</h3>
                                <p class="mt-2 line-clamp-2 text-sm text-slate-400">{{ $post->excerpt }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-8 text-center" data-sr="up">
                    <a href="{{ route('blog.index') }}" class="btn-outline">مشاهده همه مقالات</a>
                </div>
            </div>
        </section>
    @endif

    @if (!empty($partners))
        <section class="landing-section section-partners py-16 lg:py-20">
            <div class="landing-container text-center">
                <p class="mb-8 text-sm font-medium text-slate-500" data-sr="up">همکاری با برندهای معتبر</p>
                <div class="partners-marquee">
                    <div class="partners-marquee__track">
                        @foreach (array_merge($partners, $partners) as $partner)
                            <span class="partners-marquee__item">{{ $partner }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section data-section="cta" class="landing-section section-cta">
        <div class="landing-container">
            <div class="cta-portal glass-card-3d">
                <div class="cta-portal__rings" aria-hidden="true">
                    <div class="cta-portal__ring cta-portal__ring--1"></div>
                    <div class="cta-portal__ring cta-portal__ring--2"></div>
                    <div class="cta-portal__ring cta-portal__ring--3"></div>
                </div>
                <div class="cta-portal__cubes" aria-hidden="true">
                    <div class="cta-cube cta-cube--orange"></div>
                    <div class="cta-cube cta-cube--purple"></div>
                    <div class="cta-cube cta-cube--blue"></div>
                </div>
                <div class="cta-portal__content text-center" data-sr="scale">
                    <h2 class="section-heading mb-4 text-white">{{ $cta['title'] ?? 'آماده شروع هستید؟' }}</h2>
                    <p class="mx-auto mb-8 max-w-xl text-slate-400">{{ $cta['subtitle'] ?? 'محصول آماده یا پروژه اختصاصی — با یک تماس راه را پیدا می‌کنیم.' }}</p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('contact') }}" class="btn-primary">{{ $cta['primary'] ?? 'مشاوره رایگان' }}</a>
                        <a href="{{ route('services') }}" class="btn-outline">{{ $cta['secondary'] ?? 'پروژه اختصاصی' }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
