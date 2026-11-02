<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DiagnoseMigratedPagesCommand extends Command
{
    protected $signature = 'cms:diagnose-migrated-pages {slug? : Optional single slug to inspect}';

    protected $description = 'Diagnose why migrated WordPress Blade pages may not render on this environment';

    public function handle(): int
    {
        $map = config('cms_migrated_pages.pages', []);
        if (! is_array($map) || $map === []) {
            $this->error('config cms_migrated_pages.pages is empty. Run: php artisan config:clear');

            return self::FAILURE;
        }

        $slugs = $this->argument('slug')
            ? [(string) $this->argument('slug')]
            : array_keys($map);

        $rows = [];
        $problems = 0;

        foreach ($slugs as $slug) {
            $meta = $map[$slug] ?? null;
            $page = CmsPage::withTrashed()->where('slug', $slug)->first();
            $published = $page && ! $page->trashed()
                ? CmsPage::query()->published()->where('slug', $slug)->where('is_system', false)->exists()
                : false;

            $view = is_array($meta) ? (string) ($meta['view'] ?? '') : '';
            $css = is_array($meta) ? (string) ($meta['css'] ?? '') : '';
            $viewOk = $view !== '' && view()->exists($view);
            $cssOk = $css !== '' && File::isFile(public_path($css));
            $htmlKey = is_array($meta) ? (string) ($meta['key'] ?? '') : '';
            $htmlOk = $htmlKey !== '' && File::isFile(resource_path('views/pages/migrated/html/'.$htmlKey.'.html'));

            $issue = match (true) {
                $meta === null => 'not-in-config',
                ! $page => 'missing-db-row',
                $page->trashed() => 'soft-deleted',
                ! $published => 'not-published-or-system',
                ! $viewOk => 'missing-blade-view',
                ! $htmlOk => 'missing-html-file',
                ! $cssOk => 'missing-css-file',
                default => 'ok',
            };

            if ($issue !== 'ok') {
                $problems++;
            }

            $rows[] = [
                mb_strimwidth($slug, 0, 40, '…'),
                $page?->template ?? '-',
                $published ? 'yes' : 'no',
                $viewOk ? 'yes' : 'no',
                $htmlOk ? 'yes' : 'no',
                $cssOk ? 'yes' : 'no',
                $issue,
            ];
        }

        $this->table(
            ['slug', 'template', 'published', 'view', 'html', 'css', 'status'],
            $rows
        );

        $this->line('Mapped pages in config: '.count($map));
        $this->line('Config cached: '.(File::exists(base_path('bootstrap/cache/config.php')) ? 'yes' : 'no'));

        if ($problems > 0) {
            $this->warn("Found {$problems} problem(s).");
            $this->line('Suggested fixes:');
            $this->line('  php artisan optimize:clear');
            $this->line('  Ensure CmsPage rows exist (wp:migrate-pages) and are published');
            $this->line('  Ensure resources/views/pages/migrated and public/site/pages are deployed');

            return self::FAILURE;
        }

        $this->info('All checked migrated pages look deployable.');

        return self::SUCCESS;
    }
}
