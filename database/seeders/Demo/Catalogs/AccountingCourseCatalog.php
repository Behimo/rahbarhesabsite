<?php

namespace Database\Seeders\Demo\Catalogs;

use Database\Seeders\Demo\Support\CourseDefinition;
use Database\Seeders\Demo\Support\LessonDefinition;
use Database\Seeders\Demo\Support\SectionDefinition;

final class AccountingCourseCatalog
{
    /**
     * @return list<CourseDefinition>
     */
    public function courses(): array
    {
        return [
            new CourseDefinition(
                slug: 'demo-tax-return-legal',
                title: 'اظهارنامه عملکرد اشخاص حقوقی از صفر تا ارسال',
                subtitle: '۱۲ ساعت آموزش کاربردی',
                description: 'مسیر کامل تهیه و ارسال اظهارنامه عملکرد حقوقی، از جمع‌آوری مدارک تا کنترل نهایی.',
                price: 2450000,
                salePrice: 1980000,
                level: 'intermediate',
                durationMinutes: 720,
                whatYouLearn: ['تهیه کاربرگ‌های مالیاتی', 'تطبیق دفاتر با اظهارنامه', 'رفع ایرادات رایج سامانه'],
                requirements: ['آشنایی مقدماتی با حسابداری مالی', 'دسترسی به نرم‌افزار حسابداری'],
                sections: [
                    $this->section('آماده‌سازی مدارک', [
                        ['docs-gather', 'جمع‌آوری مستندات سال مالی', true],
                        ['trial-balance', 'کنترل تراز آزمایشی'],
                        ['adjustments', 'ثبت‌های اصلاحی پایان دوره'],
                    ]),
                    $this->section('تکمیل جداول اظهارنامه', [
                        ['income-table', 'جدول درآمد و هزینه'],
                        ['depreciation', 'استهلاک و اموال'],
                        ['taxable-profit', 'محاسبه سود مشمول مالیات'],
                    ]),
                    $this->section('ارسال و پیگیری', [
                        ['submit-portal', 'ارسال در درگاه مالیاتی'],
                        ['follow-up', 'پیگیری اخطارها و اصلاحیه‌ها'],
                    ]),
                ],
                sortOrder: 10,
            ),
            new CourseDefinition(
                slug: 'demo-vat-practical',
                title: 'کارگاه عملی مالیات بر ارزش افزوده',
                subtitle: '۹ ساعت',
                description: 'آموزش اجرایی ارزش افزوده برای حسابداران شرکت‌های بازرگانی و خدماتی.',
                price: 1650000,
                salePrice: null,
                level: 'intermediate',
                durationMinutes: 540,
                whatYouLearn: ['صدور صورت‌حساب استاندارد', 'محاسبه اعتبار خرید', 'تهیه اظهارنامه فصلی'],
                requirements: ['آشنایی با فاکتور فروش'],
                sections: [
                    $this->section('مبانی ارزش افزوده', [
                        ['vat-basics', 'مفاهیم پایه و نرخ‌ها', true],
                        ['taxable-scope', 'معاملات مشمول و معاف'],
                    ]),
                    $this->section('اجرای فصلی', [
                        ['purchase-credit', 'اعتبار مالیاتی خرید'],
                        ['vat-return', 'تهیه اظهارنامه فصلی'],
                        ['common-mistakes', 'خطاهای پرتکرار'],
                    ]),
                ],
                sortOrder: 11,
            ),
            new CourseDefinition(
                slug: 'demo-payroll-mastery',
                title: 'حقوق و دستمزد حرفه‌ای ۱۴۰۵',
                subtitle: '۱۴ ساعت',
                description: 'از حکم حقوقی تا لیست بیمه و مالیات حقوق، با تمرین‌های واقعی.',
                price: 3200000,
                salePrice: 2790000,
                level: 'beginner',
                durationMinutes: 840,
                whatYouLearn: ['محاسبه خالص پرداختی', 'تهیه لیست بیمه', 'اعمال پلکان مالیات حقوق'],
                requirements: ['آشنایی با اکسل'],
                sections: [
                    $this->section('اجزای حقوق', [
                        ['pay-components', 'مزایا و کسور', true],
                        ['overtime', 'اضافه‌کار و ماموریت'],
                    ]),
                    $this->section('بیمه و مالیات', [
                        ['insurance-list', 'لیست بیمه تامین اجتماعی'],
                        ['payroll-tax', 'مالیات حقوق'],
                        ['year-end', 'تسویه پایان سال'],
                    ]),
                    $this->section('گزارش‌گیری', [
                        ['payroll-reports', 'گزارش‌های مدیریتی حقوق'],
                    ]),
                ],
                sortOrder: 12,
            ),
            new CourseDefinition(
                slug: 'demo-financial-accounting',
                title: 'حسابداری مالی کاربردی برای بازار کار',
                subtitle: '۱۶ ساعت',
                description: 'ثبت رویدادها، تهیه صورت‌های مالی و کنترل‌های داخلی پایه.',
                price: 2800000,
                salePrice: null,
                level: 'beginner',
                durationMinutes: 960,
                whatYouLearn: ['ثبت رویدادهای مالی', 'تهیه ترازنامه و سود و زیان', 'بستن حساب‌ها'],
                requirements: ['علاقه به ورود به حوزه حسابداری'],
                sections: [
                    $this->section('مبانی ثبت', [
                        ['double-entry', 'حسابداری دوبل', true],
                        ['journals', 'دفتر روزنامه و کل'],
                        ['ledgers', 'معین و تفصیلی'],
                    ]),
                    $this->section('صورت‌های مالی', [
                        ['balance-sheet', 'ترازنامه'],
                        ['income-statement', 'سود و زیان'],
                        ['closing', 'بستن حساب‌ها'],
                    ]),
                ],
                sortOrder: 13,
            ),
            new CourseDefinition(
                slug: 'demo-cost-accounting',
                title: 'بهای تمام‌شده و حسابداری صنعتی',
                subtitle: '۱۰ ساعت',
                description: 'محاسبه بهای تمام‌شده تولید، مراکز هزینه و گزارش انحرافات.',
                price: 2100000,
                salePrice: 1750000,
                level: 'advanced',
                durationMinutes: 600,
                whatYouLearn: ['شناسایی مراکز هزینه', 'تسهیم هزینه‌ها', 'گزارش بهای تمام‌شده'],
                requirements: ['آشنایی با حسابداری مالی'],
                sections: [
                    $this->section('مفاهیم هزینه', [
                        ['cost-types', 'انواع هزینه‌ها', true],
                        ['cost-centers', 'مراکز هزینه'],
                    ]),
                    $this->section('محاسبات', [
                        ['allocation', 'تسهیم هزینه‌های مشترک'],
                        ['unit-cost', 'بهای تمام‌شده واحد'],
                        ['variance', 'تحلیل انحرافات'],
                    ]),
                ],
                sortOrder: 14,
            ),
            new CourseDefinition(
                slug: 'demo-excel-for-accountants',
                title: 'اکسل ویژه حسابداران',
                subtitle: '۸ ساعت — کاربردی',
                description: 'فرمول‌ها، جدول‌محوری و داشبوردهای مالی برای کار روزمره حسابداری.',
                price: 980000,
                salePrice: null,
                level: 'beginner',
                durationMinutes: 480,
                whatYouLearn: ['فرمول‌های پرکاربرد مالی', 'Pivot Table', 'ساخت گزارش ماهانه'],
                requirements: ['نصب اکسل روی سیستم'],
                sections: [
                    $this->section('ابزارهای پایه', [
                        ['excel-formulas', 'فرمول‌های ضروری', true],
                        ['lookups', 'جستجو و تطبیق داده'],
                    ]),
                    $this->section('گزارش‌سازی', [
                        ['pivot', 'جدول محوری'],
                        ['dashboard', 'داشبورد مالی ساده'],
                    ]),
                ],
                sortOrder: 15,
            ),
            new CourseDefinition(
                slug: 'demo-free-transport-cost',
                title: 'آموزش رایگان: هزینه حمل در اظهارنامه',
                subtitle: '۹۰ دقیقه — رایگان',
                description: 'پاسخ به سوال پرتکرار ثبت هزینه حمل مواد مستقیم در اظهارنامه.',
                price: 0,
                salePrice: null,
                level: 'beginner',
                durationMinutes: 90,
                whatYouLearn: ['ثبت صحیح هزینه حمل', 'اثر آن بر بهای تمام‌شده'],
                requirements: [],
                sections: [
                    $this->section('مرور موضوع', [
                        ['intro-transport', 'صورت مسئله', true],
                        ['journal-entry', 'ثبت حسابداری پیشنهادی'],
                        ['tax-impact', 'اثر مالیاتی'],
                    ]),
                ],
                sortOrder: 16,
            ),
            new CourseDefinition(
                slug: 'demo-audit-prep',
                title: 'آماده‌سازی پرونده برای حسابرسی',
                subtitle: '۷ ساعت',
                description: 'چیدمان مدارک، کاربرگ‌ها و پاسخ به پرسش‌های رایج حسابرسان.',
                price: 1490000,
                salePrice: null,
                level: 'intermediate',
                durationMinutes: 420,
                whatYouLearn: ['ساخت پرونده حسابرسی', 'کاربرگ‌های کنترلی', 'پاسخ به بندهای گزارش'],
                requirements: ['تجربه حداقل یک دوره مالی کامل'],
                sections: [
                    $this->section('پیش از حسابرسی', [
                        ['file-structure', 'ساختار پرونده', true],
                        ['evidence', 'مستندات پشتیبان'],
                    ]),
                    $this->section('حین حسابرسی', [
                        ['auditor-questions', 'پرسش‌های پرتکرار'],
                        ['adjustments-audit', 'ثبت اصلاحات پیشنهادی'],
                    ]),
                ],
                sortOrder: 17,
            ),
        ];
    }

    /**
     * @param  list<array{0: string, 1: string, 2?: bool, 3?: int}>  $lessons
     */
    private function section(string $title, array $lessons): SectionDefinition
    {
        return new SectionDefinition(
            title: $title,
            lessons: array_map(
                function (array $lesson): LessonDefinition {
                    [$slug, $lessonTitle] = $lesson;
                    $isPreview = $lesson[2] ?? false;
                    $duration = $lesson[3] ?? 720;

                    return new LessonDefinition(
                        slug: $slug,
                        title: $lessonTitle,
                        content: '<p>در این درس «'.$lessonTitle.'» را به‌صورت کاربردی بررسی می‌کنیم و نکات اجرایی آن را مرور می‌کنیم.</p>',
                        durationSeconds: $duration,
                        isFreePreview: $isPreview,
                    );
                },
                $lessons,
            ),
        );
    }
}
