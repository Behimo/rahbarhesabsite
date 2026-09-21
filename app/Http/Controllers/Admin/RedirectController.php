<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsRedirect;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function index(): View
    {
        $redirects = CmsRedirect::query()->latest()->paginate(30);

        return view('admin.redirects.index', compact('redirects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        CmsRedirect::query()->create($data);
        $this->forgetPath($data['from_path']);

        return back()->with('success', 'ریدایرکت افزوده شد.');
    }

    public function update(Request $request, CmsRedirect $redirect): RedirectResponse
    {
        $old = $redirect->from_path;
        $data = $this->validated($request, $redirect);
        $redirect->update($data);
        $this->forgetPath($old);
        $this->forgetPath($data['from_path']);

        return back()->with('success', 'ریدایرکت به‌روزرسانی شد.');
    }

    public function destroy(CmsRedirect $redirect): RedirectResponse
    {
        $path = $redirect->from_path;
        $redirect->delete();
        $this->forgetPath($path);

        return back()->with('success', 'ریدایرکت حذف شد.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'rows' => ['required', 'string'],
        ]);

        $count = 0;
        foreach (preg_split('/\r\n|\r|\n/', $request->input('rows')) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = preg_split('/\s*,\s*|\s+/', $line);
            $from = '/'.ltrim((string) ($parts[0] ?? ''), '/');
            $to = (string) ($parts[1] ?? '/');
            $code = (int) ($parts[2] ?? 301);

            if ($from === '/' || $to === '') {
                continue;
            }

            CmsRedirect::query()->updateOrCreate(
                ['from_path' => $from],
                ['to_path' => $to, 'status_code' => in_array($code, [301, 302], true) ? $code : 301, 'is_active' => true]
            );
            $this->forgetPath($from);
            $count++;
        }

        app(CacheService::class)->flushContent();

        return back()->with('success', "{$count} ریدایرکت وارد شد.");
    }

    private function validated(Request $request, ?CmsRedirect $redirect = null): array
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:255', 'unique:cms_redirects,from_path,'.($redirect?->id ?? 'NULL')],
            'to_path' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'in:301,302'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['from_path'] = '/'.ltrim($data['from_path'], '/');
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function forgetPath(string $path): void
    {
        Cache::forget('cms.redirect.'.$path);
    }
}
