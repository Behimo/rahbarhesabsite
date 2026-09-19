@php
    $logo = \App\Models\CmsSetting::get('site_logo') ?: theme_asset('images/logorahbarhesab.webp');
    $phone = $contact['phone'] ?? '02191020105';
    $phoneDisplay = $contact['phone_display'] ?? '۰۲۱-۹۱۰۲۰۱۰۵';
    $mobile = $contact['mobile'] ?? '09333658333';
    $mobileDisplay = $contact['mobile_display'] ?? '۰۹۳۳۳۶۵۸۳۳۳';
    $cartCount = (int) ($cartCount ?? 0);
@endphp

<header class="main-header">
    <div class="header-top">
        <div class="header-top-container">
            <a href="{{ route('home') }}" class="brand">
                <img src="{{ $logo }}" alt="{{ config('cms.site_name_fa') }}" />
            </a>

            <form class="search-box" action="{{ route('search') }}" method="get" role="search">
                <input
                    type="text"
                    id="searchInput"
                    name="q"
                    value="{{ request('q') }}"
                    data-search-url="{{ route('search') }}"
                    placeholder="چی دوست داری یاد بگیری ؟"
                />
                <button type="submit" class="search-button" id="searchButton" aria-label="جستجو">
                    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
                        <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </form>

            <div class="contact-box">
                <div class="contact-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="47" height="47" fill="#ffb911" viewBox="0 0 490.399 490.399" aria-hidden="true">
                        <path d="M202.431 296.462c-37.831 0-68.606 30.778-68.606 68.606 0 37.827 30.774 68.6 68.606 68.6 37.822 0 68.597-30.773 68.597-68.6s-30.775-68.606-68.597-68.606zm0 116.79c-26.567 0-48.189-21.618-48.189-48.184 0-26.572 21.623-48.19 48.189-48.19 26.568 0 48.181 21.618 48.181 48.19 0 26.567-21.613 48.184-48.181 48.184z" />
                        <path d="M451.535 200.448c1.923-6.062 37.771-91.926-189.301-171.072C213.737 12.472 164.979 5.65 122.571 5.65 61.352 5.65 20.649 23.305 10.89 54.083L1.598 83.378c-5.453 17.211 3.499 35.459 19.968 40.677 0 0 73.031 24.875 78.275 23.343h.01c13.936 0 26.248-9.307 30.635-23.158 6.908-21.782 33.615-35.853 68.047-35.853 14.076 0 28.491 2.303 42.836 6.849 25.381 8.045 47.043 22.066 61.019 39.472 13.009 16.214 17.586 33.301 12.89 48.11-5.453 17.211 3.499 35.459 19.968 40.677 0 0 71.408 22.84 74.477 23.1 28.109 2.38 34.85-24.2 34.85-24.2 19.112 21.438 28.192 51.491 24.016 81.709-4.775 34.547-28.601 65.696-60.69 79.343-4.821 2.045-9.643 3.495-14.575 4.379-.51-21.124-8.286-41.485-22.061-57.558L255.197 221.043v-31.567h-20.416v25.77h-64.708v-25.77h-20.416v31.563S56.66 307.162 34.347 329.475c-24.702 24.702-22.868 63.397-22.868 63.397 0 50.662 41.222 91.878 91.883 91.878h198.13c45.285 0 83.023-32.934 90.524-76.107 8.158-.973 16.028-3.075 23.869-6.406 38.569-16.403 66.138-53.985 72.922-95.336 10.846-66.116-37.272-106.453-37.272-106.453zm-40.944 25.519-69.184-21.932c-5.732-1.819-8.723-8.568-6.669-15.047 6.828-21.528.996-45.344-16.429-67.057-16.458-20.515-41.6-36.909-70.779-46.156-16.339-5.179-32.817-7.805-48.997-7.805-43.504 0-77.857 19.664-87.507 50.098-1.665 5.249-6.26 8.913-11.175 8.913-.996 0-72.124-22.386-72.124-22.386-5.732-1.819-8.723-8.568-6.669-15.048l9.291-29.294c6.789-21.408 41.261-34.188 92.222-34.188 40.343 0 86.509 7.87 133.503 22.769 118.939 37.702 189.229 103.716 177.924 139.38l-9.292 29.294c-2.002 6.301-8.373 10.288-14.115 8.459zm-37.632 166.904c0 39.402-32.06 71.462-71.467 71.462h-198.13c-39.408 0-71.467-32.06-71.467-71.462 0 0-2.4-25.051 16.847-48.912l115.17-108.297h77.029l115.17 108.297c18.295 21.037 16.848 48.912 16.848 48.912z" />
                    </svg>
                </div>
                <div class="contact-divider"></div>
                <div class="contact-content">
                    <strong>تلفن تماس</strong>
                    <a href="tel:{{ $phone }}">{{ $phoneDisplay }}</a>
                    <a href="tel:{{ $mobile }}">{{ $mobileDisplay }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="header-bottom">
        <div class="header-bottom-container">
            <nav class="main-nav" id="mainNav">
                @foreach ($navLinks as $link)
                    @include('theme::partials.nav-link', ['link' => $link])
                @endforeach
            </nav>

            <div class="header-actions">
                <a href="{{ route('blog.index') }}" class="cafe-button">
                    <svg aria-hidden="true" class="e-font-icon-svg e-fas-comment-dots" width="19" height="19" viewBox="0 0 512 512">
                        <path d="M256 32C114.6 32 0 125.1 0 240c0 49.6 21.4 95 57 130.7C44.5 421.1 2.7 466 2.2 466.5c-2.2 2.3-2.8 5.7-1.5 8.7S4.8 480 8 480c66.3 0 116-31.8 140.6-51.4 32.7 12.3 69 19.4 107.4 19.4 141.4 0 256-93.1 256-208S397.4 32 256 32zM128 272c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32zm128 0c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32zm128 0c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32z" />
                    </svg>
                    کافه سوال راهبرحساب
                </a>

                @auth
                    <a href="{{ route('panel.dashboard') }}" class="login-button">
                        <span>پنل کاربری</span>
                        <span class="login-arrow">
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 448 512">
                                <path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z" />
                            </svg>
                        </span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="login-button">
                        <span>ورود / ثبت نام</span>
                        <span class="login-arrow">
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 448 512">
                                <path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z" />
                            </svg>
                        </span>
                    </a>
                @endauth

                <a href="{{ route('cart.index') }}" class="cart-button" aria-label="سبد خرید">
                    @if ($cartCount > 0)
                        <span class="cart-count">{{ fa_digits($cartCount) }}</span>
                    @endif
                    <svg class="e-font-icon-svg e-eicon-basket-medium" width="27" height="27" viewBox="0 0 1000 1000">
                        <path d="M104 365C104 365 105 365 105 365H208L279 168C288 137 320 115 355 115H646C681 115 713 137 723 170L793 365H896C896 365 897 365 897 365H958C975 365 990 379 990 396S975 427 958 427H923L862 801C848 851 803 885 752 885H249C198 885 152 851 138 798L78 427H42C25 427 10 413 10 396S25 365 42 365H104ZM141 427L199 785C205 807 225 823 249 823H752C775 823 796 807 801 788L860 427H141ZM726 365L663 189C660 182 654 177 645 177H355C346 177 340 182 338 187L274 365H726ZM469 521C469 504 483 490 500 490S531 504 531 521V729C531 746 517 760 500 760S469 746 469 729V521ZM677 734C674 751 658 762 641 760 624 758 613 742 615 725L644 519C647 502 663 490 680 492S708 510 706 527L677 734ZM385 725C388 742 375 757 358 760 341 762 325 750 323 733L293 527C291 510 303 494 320 492 337 489 353 501 355 518L385 725Z" />
                    </svg>
                </a>

                <button class="mobile-menu-button" id="mobileMenuButton" aria-label="منو" type="button">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</header>
