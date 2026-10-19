<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RedirectImportRequest;
use App\Http\Requests\Admin\RedirectRequest;
use App\Models\CmsRedirect;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function index(): View
    {
        $redirects = CmsRedirect::query()->latest()->paginate(30);

        return view('admin.redirects.index', compact('redirects'));
    }

    public function store(RedirectRequest $request): RedirectResponse
    {
        $data = $request->redirectAttributes();
        CmsRedirect::query()->create($data);
        $this->forgetPath($data['from_path']);

        return back()->with('success', 'ریدایرکت افزوده شد.');
    }

    public function update(RedirectRequest $request, CmsRedirect $redirect): RedirectResponse
    {
        $old = $redirect->from_path;
        $data = $request->redirectAttributes();
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

    public function import(RedirectImportRequest $request): RedirectResponse
    {
        $result = app(\App\Services\RankMathRedirectImporter::class)->import((string) $request->input('rows'));

        app(CacheService::class)->flushContent();

        $message = "{$result['imported']} ریدایرکت وارد شد";
        if ($result['skipped'] > 0) {
            $message .= "، {$result['skipped']} رد شد";
        }
        if ($result['chains'] !== []) {
            $message .= '. هشدار زنجیره: '.implode(' | ', array_slice($result['chains'], 0, 3));
        }

        return back()->with('success', $message);
    }

    private function forgetPath(string $path): void
    {
        Cache::forget(\App\Http\Middleware\HandleCmsRedirects::cacheKey($path));
        Cache::forget(\App\Http\Middleware\HandleCmsRedirects::cacheKey('q:'.$path));
    }
}
