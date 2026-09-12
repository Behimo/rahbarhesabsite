<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsAuditLog;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function __construct(private ThemeService $themes) {}

    public function index(): View
    {
        return view('admin.themes.index', ['themes' => $this->themes->all()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['theme_zip' => ['required', 'file', 'mimes:zip', 'max:51200']]);
        $this->themes->installFromZip($request->file('theme_zip'));

        return back()->with('success', 'قالب نصب شد.');
    }

    public function activate(string $slug): RedirectResponse
        {
            $this->themes->activate($slug);

            return back()->with('success', 'قالب فعال شد.');
        }

        public function preview(string $slug): RedirectResponse
        {
            if (! is_dir(base_path('themes/' . $slug))) {
                return back()->with('error', 'قالب یافت نشد.');
            }

            return redirect()->to($this->themes->previewUrl($slug));
        }
}
