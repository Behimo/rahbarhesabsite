<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPlugin;
use App\Services\PluginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PluginController extends Controller
{
    public function __construct(private PluginService $plugins) {}

    public function index(): View
    {
        $this->plugins->discover();

        return view('admin.plugins.index', [
            'plugins' => CmsPlugin::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['plugin_zip' => ['required', 'file', 'mimes:zip', 'max:51200']]);
        $this->plugins->installFromZip($request->file('plugin_zip'));

        return back()->with('success', 'افزونه نصب شد.');
    }

    public function toggle(CmsPlugin $plugin): RedirectResponse
    {
        if ($plugin->is_active) {
            $this->plugins->deactivate($plugin);
            $message = 'افزونه غیرفعال شد.';
        } else {
            $this->plugins->activate($plugin);
            $message = 'افزونه فعال شد.';
        }

        return back()->with('success', $message);
    }
}
