<?php

namespace App\Services;

use App\Models\CmsPlugin;
use Illuminate\Support\Facades\File;

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
}
