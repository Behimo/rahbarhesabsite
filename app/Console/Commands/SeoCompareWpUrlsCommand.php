<?php

namespace App\Console\Commands;

use App\Services\Seo\UrlParityAuditor;
use Illuminate\Console\Command;
use Throwable;

class SeoCompareWpUrlsCommand extends Command
{
    protected $signature = 'seo:compare-wp-urls
        {--sitemap=https://rahbarhesab.com/sitemap_index.xml : سایت‌مپ وردپرس یا فایل محلی}
        {--with-db : افزودن دسته، تگ، صفحه‌بندی و نامک‌های قدیمی از بکاپ وردپرس}
        {--target= : دامنه‌ی استیج؛ بدون آن درخواست‌ها داخل kernel اجرا می‌شوند}
        {--compare-live : مقایسه‌ی متا و ساختار با وردپرس زنده}
        {--limit= : حداکثر تعداد آدرس‌ها}
        {--output=docs/seo/url-map.csv : مسیر CSV خروجی}';

    protected $description = 'ممیزی هم‌ارزی آدرس‌های وردپرس و Laravel بدون تغییر داده‌های محلی';

    public function handle(UrlParityAuditor $auditor): int
    {
        $limit = $this->option('limit');
        $target = rtrim((string) $this->option('target'), '/');
        if ($limit !== null && (! ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error('--limit باید عدد مثبت باشد.');

            return self::FAILURE;
        }
        if ($target !== '' && (! filter_var($target, FILTER_VALIDATE_URL)
            || ! in_array(parse_url($target, PHP_URL_SCHEME), ['http', 'https'], true)
            || ! in_array(parse_url($target, PHP_URL_PATH), [null, '', '/'], true)
            || parse_url($target, PHP_URL_QUERY) !== null || parse_url($target, PHP_URL_FRAGMENT) !== null
            || parse_url($target, PHP_URL_USER) !== null)) {
            $this->error('--target باید فقط مبدأ HTTP(S) باشد، بدون مسیر، query یا اطلاعات ورود.');

            return self::FAILURE;
        }
        $bar = $this->output->createProgressBar();
        try {
            $rows = $auditor->audit([
                'sitemap' => $this->option('sitemap'), 'with_db' => (bool) $this->option('with-db'),
                'target' => $target, 'compare_live' => (bool) $this->option('compare-live'), 'limit' => $limit,
            ], function (int $current, int $total) use ($bar) {
                $bar->setMaxSteps($total);
                $bar->setProgress($current);
            });
            $bar->finish();
            $this->newLine(2);
            $output = (string) $this->option('output');
            $auditor->writeCsv($rows, $output);
        } catch (Throwable $exception) {
            $this->newLine();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $summary = [];
        foreach ($rows as $row) {
            foreach (explode('|', $row['source']) as $source) {
                $key = $source.'|'.$row['result'];
                $summary[$key] ??= [$source, $row['result'], 0];
                $summary[$key][2]++;
            }
        }
        ksort($summary);
        $this->table(['source', 'result', 'count'], array_values($summary));
        $failures = array_filter($rows, fn ($row) => in_array($row['result'], ['missing', 'error'], true));
        foreach (array_slice($failures, 0, 20) as $row) {
            $this->warn($row['old_url'].' ['.$row['result'].'] '.$row['notes']);
        }
        $this->info(count($rows).' URLs → '.$output);

        return count(array_filter($rows, fn ($row) => in_array($row['result'], ['missing', 'error', 'redirected'], true))) > 0
            ? self::FAILURE : self::SUCCESS;
    }
}
