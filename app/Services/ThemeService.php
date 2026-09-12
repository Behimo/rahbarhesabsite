<?php

namespace App\Services;

use App\Models\CmsAuditLog;
use App\Models\CmsTheme;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use ZipArchive;

class ThemeService
{
    /** @var string|null Preview theme slug (active only for current request). */
    private ?string $preview = null;

    public function __construct(private CacheService $cache)
    {
    }


    public function setPreview(?string $slug): void
    {
        $this->preview = $slug;
    }

    public function active(): string
    {
        if ($this->preview) {
            return $this->preview;
        }

        try {
            if (Schema::hasTable('cms_themes')) {
                $dbTheme = CmsTheme::query()->where('is_active', true)->value('slug');
                if ($dbTheme) {
                    return $dbTheme;
                }
            }
        } catch (\Throwable) {
            // Database may not be ready during install/migrate.
        }

        return config('cms.active_theme', 'rahbarhesab');
    }

    public function path(?string $theme = null): string
    {
        return base_path('themes/' . ($theme ?? $this->active()));
    }

    public function layoutViewNames(): array
    {
        $names = ['layouts.site'];

        if (is_dir($this->path() . '/views/layouts')) {
            $names[] = 'theme::layouts.site';
        }

        return $names;
    }

    /** Register (replace) the active theme's views under the theme:: namespace. */
    public function registerViews(): void
    {
        $themePath = $this->path();

        if (is_dir($themePath . '/views')) {
            // Replace, not add, so only the active theme's path is registered
            // (prevents stale namespace entries when switching themes).
            View::replaceNamespace('theme', $themePath . '/views');
        }
    }

    /**
     * Resolve a view: first the active theme, then the base app view.
     */
    public function view(string $view, array $data = []): \Illuminate\Contracts\View\View
    {
        $themeView = 'theme::' . $view;

        if (View::exists($themeView)) {
            return view($themeView, $data);
        }

        Log::debug("Theme view not found, falling back to base view: {$view}");

        return view($view, $data);
    }

    /** Public URL for a theme asset (relative to public/themes/{slug}/). */
    public function asset(string $path): string
    {
        return asset('themes/' . $this->active() . '/' . ltrim($path, '/'));
    }

    public function discover(): void
    {
        $themesPath = base_path('themes');

        if (!is_dir($themesPath)) {
            return;
        }

        foreach (File::directories($themesPath) as $dir) {
            $manifest = $this->readManifest(basename($dir));

            if (!$manifest) {
                continue;
            }

            CmsTheme::query()->updateOrCreate(
                ['slug' => $manifest['slug']],
                [
                    'name' => $manifest['name'],
                    'version' => $manifest['version'] ?? '1.0.0',
                    'manifest' => $manifest,
                    'is_active' => $manifest['slug'] === $this->active(),
                ]
            );
        }
    }

    public function all(): array
    {
        $this->discover();

        return CmsTheme::query()->orderBy('name')->get()->all();
    }

    public function activate(string $slug): void
    {
        if (!is_dir($this->path($slug))) {
            throw new \RuntimeException('قالب یافت نشد.');
        }

        CmsTheme::query()->update(['is_active' => false]);
        CmsTheme::query()->where('slug', $slug)->update(['is_active' => true]);

        CmsAuditLog::record('theme.activated', null, ['slug' => $slug]);
        $this->cache->flushContent();
    }

    public function installFromZip(UploadedFile $file): CmsTheme
    {
        $tmp = storage_path('app/theme-uploads/' . Str::uuid() . '.zip');
        File::ensureDirectoryExists(dirname($tmp));
        $file->move(dirname($tmp), basename($tmp));

        $extractPath = storage_path('app/theme-uploads/' . Str::uuid());
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            throw new \RuntimeException('فایل ZIP نامعتبر است.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '..')) {
                throw new \RuntimeException('فایل ZIP ناامن است.');
            }
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $manifestPath = $this->findManifest($extractPath);
        if (!$manifestPath) {
            File::deleteDirectory($extractPath);
            throw new \RuntimeException('theme.json یافت نشد.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $slug = $manifest['slug'] ?? basename(dirname($manifestPath));
        $target = base_path('themes/' . $slug);

        if (is_dir($target)) {
            File::deleteDirectory($target);
        }

        File::move(dirname($manifestPath), $target);
        File::deleteDirectory($extractPath);
        @unlink($tmp);

        $this->discover();

        return CmsTheme::query()->where('slug', $slug)->firstOrFail();
    }

    public function previewUrl(string $slug): string
    {
        return url('/?preview_theme=' . $slug);
    }

    private function readManifest(string $slug): ?array
    {
        $path = base_path("themes/{$slug}/theme.json");

        if (!file_exists($path)) {
            return null;
        }

        $manifest = json_decode(file_get_contents($path), true);
        $manifest['slug'] = $manifest['slug'] ?? $slug;

        return $manifest;
    }

    private function findManifest(string $dir): ?string
    {
        $direct = $dir . '/theme.json';
        if (file_exists($direct)) {
            return $direct;
        }

        foreach (File::directories($dir) as $sub) {
            $path = $sub . '/theme.json';
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
