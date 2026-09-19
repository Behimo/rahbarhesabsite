@php
    $logo = \App\Models\CmsSetting::get('site_logo') ?: theme_asset('images/logorahbarhesab.webp');
    $email = $contact['email'] ?? 'info@rahbarhesab.com';
    $phone = $contact['phone'] ?? '02191020105';
    $phoneDisplay = $contact['phone_display'] ?? '021-91020105';
    $mobile = $contact['mobile'] ?? '09334653933';
    $mobileDisplay = $contact['mobile_display'] ?? '09334653933';
    $instagram = $contact['instagram'] ?? '#';
    $telegram = $contact['telegram'] ?? '#';
    $linkedin = $contact['linkedin'] ?? '#';
@endphp

<footer class="rahbar-footer" id="rahbarFooter">
    <div class="rahbar-footer-container">
        <button class="footer-back-top" id="footerBackToTop" type="button">
            <span>بازگشت به بالا</span>
            <svg viewBox="0 0 24 24">
                <path d="M12 19V5" />
                <path d="M7 10L12 5L17 10" />
            </svg>
        </button>

        <div class="footer-main">
            <div class="footer-about">
                <a href="{{ route('home') }}" class="footer-logo">
                    <img src="{{ $logo }}" alt="{{ config('cms.site_name_fa') }}" />
                </a>
                <h3>ما را بهتر بشناسید !</h3>
                <p>
                    راهبر حساب با نام سابق رهبر حساب همان راهیست که شما را در مسیر
                    تبدیل شدن به یک حسابدار حرفه ای آموزش داده و پشتیبانی میکند. از
                    مبتدی تا پیشرفته در کنار شماییم.
                </p>
                <div class="footer-social-box">
                    <strong>همراه ما باشید!</strong>
                    <div class="footer-socials">
                        <a href="{{ $instagram }}" class="social-link instagram" aria-label="اینستاگرام" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r="1" /></svg>
                        </a>
                        <a href="{{ $telegram }}" class="social-link telegram" aria-label="تلگرام" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24"><path d="M21 4L3 11L9.8 13.4L17 7.5L11.7 14.2L11.4 20L15 16.6L19.4 19L21 4Z" /></svg>
                        </a>
                        <a href="{{ $linkedin }}" class="social-link linkedin" aria-label="لینکدین" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24"><rect x="4" y="9" width="4" height="11" /><circle cx="6" cy="5.5" r="2" /><path d="M11 20V9H15V10.8C16 9.5 17.3 9 18.5 9C21 9 21 11 21 14V20H17V14.8C17 13.3 16.5 12.5 15.3 12.5C14 12.5 15 13.4 15 15V20Z" /></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="footer-column">
                <h3>دسترسی سریع</h3>
                <ul>
                    <li><a href="{{ route('courses.index') }}">دوره‌های آموزشی</a></li>
                    <li><a href="{{ route('blog.index') }}">اخبار و مقالات</a></li>
                    <li><a href="{{ route('cart.index') }}">پیگیری سفارشات</a></li>
                    <li><a href="{{ route('contact') }}">پشتیبانی</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>لینک های مرتبط</h3>
                <ul>
                    <li><a href="{{ route('about') }}">خدمات مشاوره راهبر حساب</a></li>
                    <li><a href="{{ route('about') }}">درباره ما</a></li>
                    <li><a href="{{ route('contact') }}">قوانین و شرایط استفاده</a></li>
                    <li><a href="{{ route('courses.index') }}">مدارک و گواهینامه ها</a></li>
                </ul>
            </div>

            <div class="footer-contact">
                <h3>ارتباط با ما</h3>
                <a href="tel:{{ $phone }}" class="footer-contact-item">
                    <span class="contact-footer-icon">
                        <svg viewBox="0 0 24 24"><path d="M6 4L9 3L12 8L10 10C11.2 12.5 13 14.3 15.5 15.5L17.5 13.5L22 16L21 19C20.5 21 18.5 22 16.5 21.5C9.5 19.5 4.5 14.5 2.5 7.5C2 5.5 4 4 6 4Z" /></svg>
                    </span>
                    <span>{{ $phoneDisplay }}</span>
                </a>
                <a href="tel:{{ $mobile }}" class="footer-contact-item">
                    <span class="contact-footer-icon">
                        <svg viewBox="0 0 24 24"><path d="M6 4L9 3L12 8L10 10C11.2 12.5 13 14.3 15.5 15.5L17.5 13.5L22 16L21 19C20.5 21 18.5 22 16.5 21.5C9.5 19.5 4.5 14.5 2.5 7.5C2 5.5 4 4 6 4Z" /></svg>
                    </span>
                    <span>{{ $mobileDisplay }}</span>
                </a>
                <a href="mailto:{{ $email }}" class="footer-contact-item">
                    <span class="contact-footer-icon">
                        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M4 7L12 13L20 7" /></svg>
                    </span>
                    <span>{{ $email }}</span>
                </a>
            </div>
        </div>

        <div class="footer-divider"></div>

        <div class="footer-bottom">
            <div class="footer-certificates"></div>
            <div class="footer-benefits">
                <div class="footer-benefit">
                    <div class="footer-benefit-icon">
                        <svg viewBox="0 0 64 64">
                            <path d="M13 33V27C13 17 21 10 32 10C43 10 51 17 51 27V33" />
                            <rect x="8" y="29" width="9" height="18" rx="4" />
                            <rect x="47" y="29" width="9" height="18" rx="4" />
                            <path d="M48 47C46 53 41 55 34 55" />
                            <rect x="29" y="52" width="8" height="5" rx="2" />
                        </svg>
                    </div>
                    <div>
                        <h4>پشتیبانی 7/24</h4>
                        <p>توسط تیم پشتیبانی حرفه ای</p>
                    </div>
                </div>
                <div class="footer-benefit">
                    <div class="footer-benefit-icon">
                        <svg viewBox="0 0 64 64">
                            <rect x="14" y="10" width="35" height="45" rx="3" />
                            <path d="M22 20H41" />
                            <path d="M22 27H38" />
                            <path d="M22 34H34" />
                            <circle cx="41" cy="43" r="7" />
                            <path d="M38 49L36 57L42 54L47 57L45 49" />
                        </svg>
                    </div>
                    <div>
                        <h4>امکان دریافت مدرک فنی و حرفه ای</h4>
                        <p>پس از گذراندن دوره ها و قبولی در آزمون</p>
                    </div>
                </div>
                <div class="footer-benefit">
                    <div class="footer-benefit-icon">
                        <svg viewBox="0 0 64 64">
                            <rect x="14" y="10" width="37" height="45" rx="3" />
                            <circle cx="31" cy="28" r="8" />
                            <path d="M27 28L30 31L35 25" />
                            <path d="M20 43H37" />
                            <path d="M46 40L54 48" />
                            <path d="M50 36L56 42" />
                        </svg>
                    </div>
                    <div>
                        <h4>گواهی نامه معتبر دیجیتالی ویژه بازار کار</h4>
                        <p>دریافت گواهی نامه پس از دوره</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('contact') }}" class="footer-floating-support" aria-label="پشتیبانی">
        <svg viewBox="0 0 24 24">
            <circle cx="12" cy="8" r="4" />
            <path d="M5 20V18 C5 14.8 8 13 12 13 C16 13 19 14.8 19 18 V20Z" />
        </svg>
    </a>
</footer>
