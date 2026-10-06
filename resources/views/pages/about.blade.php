@extends('layouts.site')

@php
    $achievements = [
        'مدرس و مشاور امور مالی و مالیاتی',
        'بیش از چندین سال فعالیت تخصصی در حوزه حسابداری',
        'آموزش تخصصی حسابداری و مالیات به هزاران دانش‌پذیر',
        'برگزاری دوره‌ها و همایش‌های تخصصی حسابداری',
        'همکاری با مجموعه‌ها و شرکت‌های مختلف',
        'ارائه خدمات تخصصی حسابداری، مالی و مالیاتی',
        'طراحی و تولید دوره‌های کاربردی ویژه بازار کار',
        'برگزاری رویدادهای تخصصی برای جامعه حسابداری',
        'آموزش کاربردی ویژه حسابداران و مدیران مالی',
        'تولید محتوای تخصصی در حوزه حسابداری و مالیات',
    ];

    $team = [
        ['name' => 'مرتضی رهبر', 'role' => 'مدیر عامل'],
        ['name' => 'واحد محتوا', 'role' => 'تولید محتوا'],
        ['name' => 'محمد آزادی', 'role' => 'مدیر تیم محتوا'],
        ['name' => 'معصومه تاریکی', 'role' => 'کارشناس محتوا'],
        ['name' => 'رضا عسگری', 'role' => 'SEO Specialist'],
        ['name' => 'محمد عسگری', 'role' => 'UI/UX Designer'],
        ['name' => 'نیما مهرابی', 'role' => 'برنامه‌نویس'],
        ['name' => 'مهدی آقایی', 'role' => 'مدیر امور مالی'],
        ['name' => 'علی فرهادی', 'role' => 'کارشناس مالی'],
        ['name' => 'مریم یوسفی', 'role' => 'پشتیبانی'],
        ['name' => 'احمد جوادی', 'role' => 'تولید محتوا'],
        ['name' => 'حامد تاریکی', 'role' => 'طراح گرافیک'],
        ['name' => 'سارا حسینی', 'role' => 'کارشناس حسابداری'],
        ['name' => 'فاطمه نظامی', 'role' => 'کارشناس محتوا'],
        ['name' => 'سمانه مقدم', 'role' => 'کارشناس فروش'],
        ['name' => 'رضا موسوی', 'role' => 'کارشناس فنی'],
        ['name' => 'محمدرضا نیکزاد', 'role' => 'کارشناس فروش'],
        ['name' => 'الهام نیک‌نژاد', 'role' => 'کارشناس پشتیبانی'],
        ['name' => 'محمد یزدانی', 'role' => 'تولید محتوا'],
        ['name' => 'ریحانه کریمی', 'role' => 'کارشناس آموزش'],
    ];
@endphp

@section('page')
<div class="about-page">
    <section class="page-hero">
        <div class="container">
            <div class="page-title-box">
                <h1>درباره ما</h1>
                <p>ما را بهتر بشناسید</p>
            </div>
        </div>
    </section>

    <section class="about-section achievements-section">
        <div class="container">
            <div class="section-heading">
                <h2>دستاوردهای تیم راهبر حساب</h2>
            </div>

            <div class="content-card achievements-card">
                <div class="achievement-text">
                    <ul>
                        @foreach ($achievements as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="achievement-image">
                    <img src="{{ asset('site/images/group-all-mentorma.webp') }}" alt="تیم راهبر حساب">
                </div>
            </div>
        </div>
    </section>

    <section class="about-section award-section">
        <div class="container">
            <div class="section-heading">
                <h2>مدرس برتر سال ۱۴۰۴ از نگاه مردم</h2>
            </div>

            <div class="content-card award-card">
                <div class="award-description">
                    <p>
                        مجموعه راهبر حساب با هدف آموزش تخصصی و کاربردی حسابداری
                        فعالیت خود را آغاز کرده است.
                    </p>
                    <p>
                        کسب عنوان مدرس برتر سال ۱۴۰۴ نتیجه اعتماد دانش‌پذیران،
                        حسابداران و همراهان مجموعه راهبر حساب بوده است.
                    </p>
                    <p>
                        تلاش ما همیشه ارائه آموزش‌های کاربردی، به‌روز و متناسب
                        با نیاز واقعی بازار کار بوده است.
                    </p>
                </div>

                <div class="award-image">
                    <img src="{{ asset('site/images/indexsnew.webp') }}" alt="مدرس برتر سال ۱۴۰۴">
                    <button class="play-btn" type="button" aria-label="پخش ویدئو" data-video="">
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section about-text-section">
        <div class="container">
            <article class="about-article">
                <h2>شروع داستان راهبر حساب...</h2>

                <p>
                    داستان راهبر حساب از سال‌ها تجربه در حوزه حسابداری و آموزش
                    تخصصی آغاز شد. هدف اصلی ما ایجاد بستری حرفه‌ای برای آموزش
                    حسابداری، مالیات و مهارت‌های مورد نیاز بازار کار است.
                </p>

                <p>
                    ما تلاش کرده‌ایم آموزش را از فضای صرفاً تئوری خارج کرده و
                    تجربه‌ای کاملاً کاربردی برای دانش‌پذیران فراهم کنیم؛ به‌گونه‌ای
                    که بتوانند آموخته‌های خود را مستقیماً در محیط واقعی کار
                    استفاده کنند.
                </p>

                <h3>ارزش‌های راهبر حساب</h3>

                <ul class="article-list">
                    <li>آموزش کاربردی و مبتنی بر نیاز بازار کار</li>
                    <li>به‌روز نگه داشتن محتوای آموزشی</li>
                    <li>پشتیبانی تخصصی از دانش‌پذیران</li>
                    <li>تمرکز بر رشد مهارتی و حرفه‌ای حسابداران</li>
                </ul>

                <h3>تولد محصولات و خدمات راهبر حساب</h3>

                <p>
                    در ادامه مسیر و با شناخت نیازهای جامعه حسابداری، محصولات و
                    خدمات مختلفی شکل گرفت تا حسابداران بتوانند علاوه بر آموزش،
                    ابزارها و خدمات مورد نیاز خود را نیز در یک مجموعه تخصصی
                    دریافت کنند.
                </p>

                <h3>راهبر آکادمی</h3>
                <p>
                    ارائه دوره‌های تخصصی و کاربردی حسابداری، مالیات و مهارت‌های
                    حرفه‌ای بازار کار.
                </p>

                <h3>راهبر سیستم</h3>
                <p>
                    ارائه نرم‌افزارها و ابزارهای تخصصی برای ساده‌تر شدن فرایندهای
                    مالی و حسابداری کسب‌وکارها.
                </p>

                <h3>راهبر مالی</h3>
                <p>
                    ارائه خدمات تخصصی حسابداری و مالیاتی به کسب‌وکارها.
                </p>

                <h3>راهبر مشاور</h3>
                <p>
                    ارائه خدمات مشاوره تخصصی در زمینه حسابداری، مالی و مالیاتی.
                </p>

                <h3>راهبر دسک</h3>
                <p>
                    ارائه ابزارهای ارتباط و پشتیبانی تخصصی برای کاربران.
                </p>

                <h3>راهبر تیم</h3>
                <p>
                    ابزار مدیریت تیم، وظایف، مشتریان و فرایندهای کاری مجموعه‌ها.
                </p>

                <p class="about-final-text">
                    امروز راهبر حساب تنها یک مجموعه آموزشی نیست؛ بلکه یک اکوسیستم
                    تخصصی برای جامعه حسابداری است.
                </p>
            </article>
        </div>
    </section>

    <section class="about-section about-cta-section">
        <div class="container">
            <div class="about-cta">
                <h3>تیم راهبر حساب</h3>
                <p>ما در راهبر حساب با یک تیم متخصص در کنار شما هستیم.</p>
            </div>
        </div>
    </section>

    <section class="about-section team-section">
        <div class="container">
            <div class="team-grid">
                @foreach ($team as $member)
                    <div class="team-member">
                        <h4>{{ $member['name'] }}</h4>
                        <span>{{ $member['role'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <dialog class="about-video-dialog" aria-labelledby="about-video-title">
        <button class="about-video-dialog__close" type="button" data-about-video-close>بستن</button>
        <h2 id="about-video-title">ویدئو مدرس برتر سال ۱۴۰۴</h2>
        <div class="about-video-dialog__frame">
            <iframe title="ویدئو مدرس برتر سال ۱۴۰۴" allow="autoplay; fullscreen" allowfullscreen hidden></iframe>
            <p class="about-video-dialog__empty">لینک ویدئو هنوز تنظیم نشده است.</p>
        </div>
    </dialog>
</div>
@endsection
