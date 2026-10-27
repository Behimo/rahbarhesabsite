<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seeded system-page meta replaced by the live WordPress values; rows edited in the admin keep their text.
     *
     * @var array<string, array{0: array{string, string}, 1: array{string, string}}>
     */
    private const SYSTEM_META = [
        'home' => [
            ['راهبر حساب | موسسه آموزش حسابداری و خدمات مالی', 'موسسه آموزش حسابداری راهبر حساب و اجرای خدمات مالی و مالیاتی'],
            ['موسسه آموزش حسابداری راهبر حساب — دوره‌های کاربردی حسابداری و مالیات در سراسر ایران.', 'موسسه آموزش حسابداری راهبر حساب ارائه دهنده آموزش، خدمات اجرایی و نرم افزار های مالی و مالیاتی'],
        ],
        'about' => [
            ['درباره راهبر حساب | موسسه آموزش حسابداری', 'درباره ما - راهبر حساب'],
            ['آشنایی با موسسه راهبر حساب، تیم آموزشی و خدمات مالی و مالیاتی.', 'ما را بهتر بشناسید'],
        ],
        'contact' => [
            ['تماس با راهبر حساب | مشاوره رایگان', 'تماس با ما - راهبر حساب'],
            ['تماس با موسسه راهبر حساب برای مشاوره آموزشی و خدمات مالی.', 'ارتباط با راهبر حساب، در سریع‌ترین زمان پاسخگوی شما هستیم. راهنمای استفاده از محصولات خریداری شده در صورتی که محصولات آموزشی خریده اید و در مورد شروع و نحوه'],
        ],
    ];

    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('robots', 500)->nullable()->after('meta_description');
        });

        $this->syncSystemMeta(0, 1);
    }

    public function down(): void
    {
        $this->syncSystemMeta(1, 0);

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('robots');
        });
    }

    private function syncSystemMeta(int $from, int $to): void
    {
        foreach (self::SYSTEM_META as $slug => [$title, $description]) {
            DB::table('cms_pages')->where('slug', $slug)->where('meta_title', $title[$from])->update(['meta_title' => $title[$to]]);
            DB::table('cms_pages')->where('slug', $slug)->where('meta_description', $description[$from])->update(['meta_description' => $description[$to]]);
        }
    }
};
