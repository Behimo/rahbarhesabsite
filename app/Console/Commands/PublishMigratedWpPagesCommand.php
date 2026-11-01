<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use App\Services\MigratedWpPageConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishMigratedWpPagesCommand extends Command
{
    protected $signature = 'cms:publish-migrated-wp-pages
                            {slug? : Convert a single page slug}
                            {--cleanup : After publishing, strip WP HTML/CSS from DB and delete migrated-wp assets}
                            {--only-cleanup : Skip publish; only run cleanup for pages already in the map}';

    protected $description = 'Publish WordPress-imported CmsPages as Blade + scoped CSS files';

    public function handle(MigratedWpPageConverter $converter): int
    {
        if ($this->option('only-cleanup')) {
            return $this->cleanup($converter) ? self::SUCCESS : self::FAILURE;
        }

        $slug = $this->argument('slug');
        $query = CmsPage::query()->whereNotNull('wp_post_id');
        if (is_string($slug) && $slug !== '') {
            $query->where('slug', $slug);
        } else {
            $query->whereIn('slug', array_keys(MigratedWpPageConverter::SLUG_KEYS));
        }

        $pages = $query->orderBy('id')->get();
        if ($pages->isEmpty()) {
            $this->error('No matching WordPress-imported pages found.');

            return self::FAILURE;
        }

        $map = config('cms_migrated_pages.pages', []);
        if (! is_array($map)) {
            $map = [];
        }

        $bar = $this->output->createProgressBar($pages->count());
        $bar->start();

        foreach ($pages as $page) {
            $meta = $converter->convert($page);
            $map[(string) $page->slug] = [
                'key' => $meta['key'],
                'view' => $meta['view'],
                'css' => $meta['css'],
            ];
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $converter->writeConfig($map);
        $this->info('Published '.$pages->count().' page(s). Config updated.');

        if ($this->option('cleanup')) {
            return $this->cleanup($converter) ? self::SUCCESS : self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function cleanup(MigratedWpPageConverter $converter): bool
    {
        $map = config('cms_migrated_pages.pages', []);
        if (! is_array($map) || $map === []) {
            // Reload from disk in case config was just written.
            $fresh = require config_path('cms_migrated_pages.php');
            $map = is_array($fresh['pages'] ?? null) ? $fresh['pages'] : [];
        }

        if ($map === []) {
            $this->error('No migrated pages in config; nothing to clean up.');

            return false;
        }

        $slugs = array_keys($map);
        $pages = CmsPage::query()->whereIn('slug', $slugs)->get();
        $cleaned = 0;

        foreach ($pages as $page) {
            $content = is_array($page->content) ? $page->content : [];
            unset($content['body_html'], $content['stylesheet_urls'], $content['import_source']);
            $content['migrated_to_blade'] = true;
            $page->content = $content;
            $page->template = 'content';
            $page->save();
            $cleaned++;

            if ($page->wp_post_id) {
                $dir = public_path('migrated-wp/css/'.$page->wp_post_id);
                if (is_dir($dir)) {
                    File::deleteDirectory($dir);
                }
            }
        }

        $scopeDir = public_path('site/page-scope');
        if (is_dir($scopeDir)) {
            File::deleteDirectory($scopeDir);
        }

        $this->info("Cleaned {$cleaned} CmsPage row(s), removed migrated-wp CSS dirs and page-scope cache.");

        return true;
    }
}
