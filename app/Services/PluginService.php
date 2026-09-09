<?php

namespace App\Services;

use App\Models\CmsAuditLog;
use App\Models\CmsPlugin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class PluginService
{
    public function discover(): void
    {
        $pluginsPath = base_path('plugins');

        if (! is_dir($pluginsPath)) {
            return;
        }

        foreach (File::directories($pluginsPath) as $dir) {
            $manifestPath = $dir.'/plugin.json';

            if (! file_exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);

            CmsPlugin::query()->updateOrCreate(
                ['slug' => $manifest['slug']],
                [
                    'name' => $manifest['name'],
                    'version' => $manifest['version'] ?? '1.0.0',
                    'provider_class' => $manifest['provider'] ?? null,
                ]
            );
        }
    }

    public function bootActive(): void
    {
        CmsPlugin::query()->where('is_active', true)->each(function (CmsPlugin $plugin) {
            if ($plugin->provider_class && class_exists($plugin->provider_class)) {
                app()->register($plugin->provider_class);
            }
        });
    }

    public function activate(CmsPlugin $plugin): void
    {
        $plugin->update(['is_active' => true]);
        CmsAuditLog::record('plugin.activated', $plugin);
    }

    public function deactivate(CmsPlugin $plugin): void
    {
        $plugin->update(['is_active' => false]);
        CmsAuditLog::record('plugin.deactivated', $plugin);
    }

    public function installFromZip(UploadedFile $file): CmsPlugin
    {
        $tmp = storage_path('app/plugin-uploads/'.Str::uuid().'.zip');
        File::ensureDirectoryExists(dirname($tmp));
        $file->move(dirname($tmp), basename($tmp));

        $extractPath = storage_path('app/plugin-uploads/'.Str::uuid());
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            throw new \RuntimeException('فایل ZIP نامعتبر است.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_contains($zip->getNameIndex($i), '..')) {
                throw new \RuntimeException('فایل ZIP ناامن است.');
            }
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $manifestPath = $this->findManifest($extractPath);
        if (! $manifestPath) {
            File::deleteDirectory($extractPath);
            throw new \RuntimeException('plugin.json یافت نشد.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $slug = $manifest['slug'] ?? basename(dirname($manifestPath));
        $target = base_path('plugins/'.$slug);

        if (is_dir($target)) {
            File::deleteDirectory($target);
        }

        File::move(dirname($manifestPath), $target);
        File::deleteDirectory($extractPath);
        @unlink($tmp);

        $this->discover();

        return CmsPlugin::query()->where('slug', $slug)->firstOrFail();
    }

    private function findManifest(string $dir): ?string
    {
        $direct = $dir.'/plugin.json';
        if (file_exists($direct)) {
            return $direct;
        }

        foreach (File::directories($dir) as $sub) {
            $path = $sub.'/plugin.json';
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
